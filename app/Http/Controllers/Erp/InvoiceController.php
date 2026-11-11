<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\SalesPackage;
use App\Models\Task;
use App\Services\AdminNotifier;
use App\Services\AuditService;
use App\Services\EventNotifier;
use App\Services\LedgerService;
use App\Services\NumberSequenceService;
use App\Services\PdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'items', 'duplicateOf', 'creator']);

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('payment_status')) {
            $query->where('payment_status', $request->get('payment_status'));
        }
        if ($request->get('q')) {
            $q = $request->get('q');
            $query->where(function ($query) use ($q): void {
                $query->where('invoice_number', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($clientQuery) use ($q): void {
                        $clientQuery->where('business_name', 'like', "%{$q}%")
                            ->orWhere('legal_name', 'like', "%{$q}%")
                            ->orWhere('client_number', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        // Same-requirements badge: identical line-item descriptions shared by
        // invoices belonging to DIFFERENT clients (quote reuse / copy-paste).
        $duplicateDescriptions = InvoiceItem::query()
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->selectRaw('TRIM(LOWER(invoice_items.description)) as descr, COUNT(DISTINCT invoices.client_id) as clients')
            ->whereNotNull('invoice_items.description')
            ->where('invoice_items.description', '!=', '')
            ->groupBy('descr')
            ->havingRaw('COUNT(DISTINCT invoices.client_id) > 1')
            ->pluck('descr');

        $duplicateSet = $duplicateDescriptions->flip();

        foreach ($invoices as $invoice) {
            $descriptions = $invoice->items
                ->map(fn ($item) => trim(mb_strtolower((string) $item->description)))
                ->filter(fn ($descr) => $descr !== '')
                ->values();
            $invoice->same_requirements = $descriptions->contains(fn ($descr) => $duplicateSet->has($descr));
        }

        return view('erp.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $clients = Client::whereIn('status', ['active', 'lead'])->orderBy('business_name')->get();
        $packages = SalesPackage::with('items')->where('is_active', true)->orderBy('name')->get();

        return view('erp.invoices.create', compact('clients', 'packages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:USD,GBP,EUR,PKR'],
            'issue_date' => ['nullable', 'date'],
            'due_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['invoice_number'] = NumberSequenceService::next('invoices', 'INV-');
        $data['status'] = 'draft';
        $data['payment_status'] = 'unpaid';
        $data['created_by'] = auth()->id();

        $subtotal = 0;
        $items = [];
        foreach ($data['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $subtotal += $total;
            $items[] = new InvoiceItem([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $total,
            ]);
        }

        $discount = $request->input('discount', 0);
        $tax = $request->input('tax', 0);
        $total = $subtotal - $discount + $tax;

        $data['subtotal'] = $subtotal;
        $data['discount'] = $discount;
        $data['tax'] = $tax;
        $data['total'] = $total;
        $data['balance'] = $total;

        $invoice = Invoice::create($data);
        $invoice->items()->saveMany($items);

        AuditService::log('INVOICE_CREATE', 'invoice', $invoice->id, ['number' => $invoice->invoice_number]);

        return redirect()->route('erp.invoices.show', $invoice)->with('success', 'Invoice created.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'creator', 'payments', 'statusHistory.changedBy', 'duplicateOf']);

        return view('erp.invoices.show', compact('invoice'));
    }

    /**
     * Lock the invoice — prevents any further edits, records exchange rate.
     *
     * Accrual accounting entry at lock:
     *   DR 1300 Accounts Receivable
     *   CR 4000 Revenue
     */
    public function lock(Invoice $invoice)
    {
        if ($invoice->isLocked()) {
            return back()->with('error', 'Invoice is already locked.');
        }

        $allowedFrom = ['draft', 'sent'];
        if (! in_array($invoice->status, $allowedFrom)) {
            return back()->with('error', 'Invoice cannot be locked from status: '.$invoice->status);
        }

        $from = $invoice->status;
        $rate = LedgerService::rateToPkr($invoice->currency);

        try {
            DB::transaction(function () use ($invoice, $from, $rate) {
                $invoice->update([
                    'status' => 'locked',
                    'locked_at' => now(),
                    'locked_by' => auth()->id(),
                    'exchange_rate' => $rate,
                ]);

                $invoice->statusHistory()->create([
                    'from_status' => $from,
                    'to_status' => 'locked',
                    'changed_by' => auth()->id(),
                ]);

                // Accrual entry: DR Accounts Receivable, CR Revenue
                LedgerService::post('1300', 'debit', $invoice->total, $invoice->currency, [
                    'invoice_id' => $invoice->id,
                ], "Invoice {$invoice->invoice_number} — AR recognised");
                LedgerService::post('4000', 'credit', $invoice->total, $invoice->currency, [
                    'invoice_id' => $invoice->id,
                ], "Invoice {$invoice->invoice_number} — revenue recognised");
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Invoice lock failed: '.$e->getMessage());
        }

        AuditService::log('INVOICE_LOCK', 'invoice', $invoice->id, ['number' => $invoice->invoice_number, 'rate' => $rate]);

        EventNotifier::invoiceStatusChanged($invoice, 'locked');

        AdminNotifier::notify(
            'invoice_locked',
            'Invoice locked: '.$invoice->invoice_number,
            auth()->user()->name.' locked '.$invoice->invoice_number.' — GL posted (DR 1300 / CR 4000), rate '.($rate ?? 'N/A').'.',
            '/erp/invoices/'.$invoice->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Invoice locked. Exchange rate captured: '.($rate ?? 'N/A'));
    }

    public function send(Invoice $invoice)
    {
        if ($invoice->status === 'draft') {
            return back()->with('error', 'Please lock the invoice before sending.');
        }

        $allowedFrom = ['locked'];
        if (! in_array($invoice->status, $allowedFrom)) {
            return back()->with('error', 'Invoice cannot be sent from status: '.$invoice->status);
        }

        $from = $invoice->status;

        DB::transaction(function () use ($invoice, $from) {
            $invoice->update(['status' => 'sent']);
            $invoice->statusHistory()->create(['from_status' => $from, 'to_status' => 'sent', 'changed_by' => auth()->id()]);
            AuditService::log('INVOICE_SEND', 'invoice', $invoice->id);
        });

        EventNotifier::invoiceSent($invoice);
        EventNotifier::invoiceStatusChanged($invoice, 'sent');

        AdminNotifier::notify(
            'invoice_sent',
            'Invoice sent: '.$invoice->invoice_number,
            auth()->user()->name.' sent '.$invoice->invoice_number.' to '.($invoice->client?->business_name ?? 'client').' ('.$invoice->currency.' '.number_format((float) $invoice->total, 2).').',
            '/erp/invoices/'.$invoice->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Invoice marked as sent. Client + admin emails queued.');
    }

    public function cancel(Invoice $invoice, Request $request)
    {
        $allowedFrom = ['draft', 'locked', 'sent'];
        if (! in_array($invoice->status, $allowedFrom)) {
            return back()->with('error', 'Invoice cannot be cancelled from status: '.$invoice->status);
        }

        $from = $invoice->status;
        $reason = $request->input('reason', 'Cancelled');

        DB::transaction(function () use ($invoice, $from, $reason) {
            $invoice->update(['status' => 'cancelled']);
            $invoice->statusHistory()->create(['from_status' => $from, 'to_status' => 'cancelled', 'changed_by' => auth()->id(), 'reason' => $reason]);
            AuditService::log('INVOICE_CANCEL', 'invoice', $invoice->id, ['reason' => $reason]);
        });

        EventNotifier::invoiceStatusChanged($invoice, 'cancelled');

        AdminNotifier::notify(
            'invoice_cancelled',
            'Invoice cancelled: '.$invoice->invoice_number,
            auth()->user()->name.' cancelled '.$invoice->invoice_number.' — reason: '.$reason,
            '/erp/invoices/'.$invoice->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Invoice cancelled. Client + admin emails queued.');
    }

    /**
     * Duplicate an invoice — always allocates a brand-new invoice number
     * (numbers are never reused) and flags the copy as a duplicate.
     */
    public function duplicate(Invoice $invoice)
    {
        $invoice->load('items');

        $new = Invoice::create([
            'invoice_number' => NumberSequenceService::next('invoices', 'INV-'),
            'client_id' => $invoice->client_id,
            'quotation_id' => $invoice->quotation_id,
            'duplicate_of_id' => $invoice->id,
            'currency' => $invoice->currency,
            'exchange_rate' => $invoice->exchange_rate,
            'subtotal' => $invoice->subtotal,
            'discount' => $invoice->discount,
            'tax' => $invoice->tax,
            'total' => $invoice->total,
            'balance' => $invoice->total,
            'paid_amount' => 0,
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'issue_date' => $invoice->issue_date,
            'due_date' => $invoice->due_date,
            'notes' => $invoice->notes,
            'terms' => $invoice->terms,
            'created_by' => auth()->id(),
        ]);

        foreach ($invoice->items as $item) {
            $new->items()->create([
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'total' => $item->total,
            ]);
        }

        $new->statusHistory()->create([
            'from_status' => null,
            'to_status' => 'draft',
            'changed_by' => auth()->id(),
            'reason' => 'Duplicated from '.$invoice->invoice_number,
        ]);

        AuditService::log('INVOICE_DUPLICATE', 'invoice', $new->id, [
            'duplicate_of' => $invoice->id,
            'original_number' => $invoice->invoice_number,
            'new_number' => $new->invoice_number,
        ]);

        AdminNotifier::notify(
            'invoice_duplicated',
            'Invoice duplicated: '.$new->invoice_number,
            auth()->user()->name.' duplicated '.$invoice->invoice_number.' → '.$new->invoice_number.' (new number generated).',
            '/erp/invoices/'.$new->id,
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.invoices.show', $new)
            ->with('success', "Duplicated {$invoice->invoice_number} → {$new->invoice_number} (new number generated).");
    }

    public function destroy(Invoice $invoice)
    {
        if ($invoice->status !== 'draft') {
            return back()->with('error', 'Only draft invoices can be deleted. Cancel sent/locked invoices instead — they are audit records.');
        }

        $invoice->items()->delete();
        $invoice->delete();

        AuditService::log('INVOICE_DELETE', 'invoice', $invoice->id, ['number' => $invoice->invoice_number, 'deleted_by' => auth()->id()]);

        AdminNotifier::notify(
            'invoice_deleted',
            'Invoice deleted: '.$invoice->invoice_number,
            auth()->user()->name.' deleted draft invoice '.$invoice->invoice_number.' ('.$invoice->currency.' '.number_format((float) $invoice->total, 2).').',
            '/erp/invoices',
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.invoices.index')->with('success', 'Invoice deleted.');
    }

    public function pdf(Invoice $invoice)
    {
        // Scoped access: team_associate can only access invoices for their assigned clients
        if (auth()->user()->hasRole('team_associate')) {
            if ($invoice->client_id) {
                $hasAccess = Task::where('client_id', $invoice->client_id)
                    ->whereHas('assignments', function ($q) {
                        $q->where('user_id', auth()->id())->whereNull('unassigned_at');
                    })->exists();
                if (! $hasAccess) {
                    abort(403, 'You do not have access to this invoice.');
                }
            }
        }

        return PdfService::invoice($invoice)->stream("invoice-{$invoice->invoice_number}.pdf");
    }
}
