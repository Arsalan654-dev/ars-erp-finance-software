<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\SalesPackage;
use App\Services\AdminNotifier;
use App\Services\AuditService;
use App\Services\EventNotifier;
use App\Services\LedgerService;
use App\Services\NumberSequenceService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class QuotationController extends Controller
{
    public function index(Request $request)
    {
        $query = Quotation::with(['client', 'creator', 'duplicateOf']);

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('q')) {
            $q = $request->get('q');
            $query->where(function ($query) use ($q): void {
                $query->where('quotation_number', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($clientQuery) use ($q): void {
                        $clientQuery->where('business_name', 'like', "%{$q}%")
                            ->orWhere('legal_name', 'like', "%{$q}%")
                            ->orWhere('client_number', 'like', "%{$q}%")
                            ->orWhere('email', 'like', "%{$q}%");
                    });
            });
        }

        $quotations = $query->latest()->paginate(15)->withQueryString();

        return view('erp.quotations.index', compact('quotations'));
    }

    public function create()
    {
        $clients = Client::whereIn('status', ['active', 'lead'])->orderBy('business_name')->get();
        $packages = SalesPackage::with('items')->where('is_active', true)->orderBy('name')->get();

        return view('erp.quotations.create', compact('clients', 'packages'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:USD,GBP,EUR'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['quotation_number'] = NumberSequenceService::next('quotations', 'QTN-');
        $data['status'] = 'draft';
        $data['created_by'] = auth()->id();
        $data['exchange_rate'] = $data['exchange_rate'] ?? LedgerService::rateToPkr($data['currency']);

        $subtotal = 0;
        $items = [];
        foreach ($data['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $subtotal += $total;
            $items[] = new QuotationItem([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $total,
            ]);
        }

        $data['subtotal'] = $subtotal;
        $data['discount'] = $request->input('discount', 0);
        $data['tax'] = $request->input('tax', 0);
        $data['total'] = $subtotal - $data['discount'] + $data['tax'];

        $quotation = Quotation::create($data);
        $quotation->items()->saveMany($items);

        AuditService::log('QUOTATION_CREATE', 'quotation', $quotation->id, ['number' => $quotation->quotation_number]);

        return redirect()->route('erp.quotations.show', $quotation)->with('success', 'Quotation created.');
    }

    public function show(Quotation $quotation)
    {
        $quotation->load(['client', 'items', 'creator', 'statusHistory.changedBy']);

        return view('erp.quotations.show', compact('quotation'));
    }

    public function edit(Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return back()->with('error', 'Only draft quotations can be edited.');
        }

        $clients = Client::whereIn('status', ['active', 'lead'])->orderBy('business_name')->get();
        $packages = SalesPackage::with('items')->where('is_active', true)->orderBy('name')->get();

        return view('erp.quotations.edit', compact('quotation', 'clients', 'packages'));
    }

    public function update(Request $request, Quotation $quotation)
    {
        if ($quotation->status !== 'draft') {
            return back()->with('error', 'Only draft quotations can be edited.');
        }

        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:USD,GBP,EUR'],
            'exchange_rate' => ['nullable', 'numeric', 'min:0.000001'],
            'valid_until' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'terms' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'items.*.unit_price' => ['required', 'numeric', 'min:0'],
        ]);

        $data['exchange_rate'] = $data['exchange_rate'] ?? LedgerService::rateToPkr($data['currency']);

        $subtotal = 0;
        $itemsData = [];
        foreach ($data['items'] as $item) {
            $total = $item['quantity'] * $item['unit_price'];
            $subtotal += $total;
            $itemsData[] = [
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $total,
            ];
        }

        $discount = $request->input('discount', 0);
        $tax = $request->input('tax', 0);
        $total = $subtotal - $discount + $tax;

        $quotation->update([
            'client_id' => $data['client_id'],
            'currency' => $data['currency'],
            'exchange_rate' => $data['exchange_rate'],
            'valid_until' => $data['valid_until'] ?? null,
            'notes' => $data['notes'] ?? null,
            'terms' => $data['terms'] ?? null,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'total' => $total,
            // Once the quotation is edited/fixed the Duplicate badge clears.
            'duplicate_of_id' => null,
        ]);

        // Replace items
        $quotation->items()->delete();
        foreach ($itemsData as $itemData) {
            $quotation->items()->create($itemData);
        }

        AuditService::log('QUOTATION_UPDATE', 'quotation', $quotation->id, ['number' => $quotation->quotation_number]);

        return redirect()->route('erp.quotations.show', $quotation)->with('success', 'Quotation updated.');
    }

    public function send(Quotation $quotation)
    {
        $quotation->update(['status' => 'sent']);
        $quotation->statusHistory()->create(['from_status' => 'draft', 'to_status' => 'sent', 'changed_by' => auth()->id()]);

        EventNotifier::quotationSent($quotation);

        AuditService::log('QUOTATION_SEND', 'quotation', $quotation->id);

        AdminNotifier::notify(
            'quotation_sent',
            'Quotation sent: '.$quotation->quotation_number,
            auth()->user()->name.' sent '.$quotation->quotation_number.' to '.($quotation->client?->business_name ?? 'client').' ('.$quotation->currency.' '.number_format((float) $quotation->total, 2).'). Email queued to client + admin.',
            '/erp/quotations/'.$quotation->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Quotation marked as sent. Client + admin emails queued.');
    }

    public function accept(Quotation $quotation)
    {
        if ($quotation->status !== 'sent') {
            return back()->with('error', 'Only sent quotations can be accepted.');
        }

        $quotation->update(['status' => 'accepted']);
        $quotation->statusHistory()->create(['from_status' => 'sent', 'to_status' => 'accepted', 'changed_by' => auth()->id()]);

        AuditService::log('QUOTATION_ACCEPT', 'quotation', $quotation->id);

        EventNotifier::quotationStatusChanged($quotation, 'accepted');

        AdminNotifier::notify(
            'quotation_accepted',
            'Quotation accepted: '.$quotation->quotation_number,
            ($quotation->client?->business_name ?? 'Client').' accepted '.$quotation->quotation_number.' ('.$quotation->currency.' '.number_format((float) $quotation->total, 2).').',
            '/erp/quotations/'.$quotation->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Quotation accepted. Client + admin emails queued.');
    }

    public function convertToInvoice(Quotation $quotation)
    {
        $quotation->load('items');

        if ($quotation->status !== 'accepted') {
            return back()->with('error', 'Only accepted quotations can be converted to invoices.');
        }

        $invoice = Invoice::create([
            'invoice_number' => NumberSequenceService::next('invoices', 'INV-'),
            'client_id' => $quotation->client_id,
            'quotation_id' => $quotation->id,
            'currency' => $quotation->currency,
            'exchange_rate' => $quotation->exchange_rate,
            'subtotal' => $quotation->subtotal,
            'discount' => $quotation->discount,
            'tax' => $quotation->tax,
            'total' => $quotation->total,
            'balance' => $quotation->total,
            'status' => 'draft',
            'payment_status' => 'unpaid',
            'issue_date' => now(),
            'created_by' => auth()->id(),
        ]);

        foreach ($quotation->items as $item) {
            $invoice->items()->create($item->toArray());
        }

        $quotation->update(['status' => 'converted']);
        $quotation->statusHistory()->create(['from_status' => 'accepted', 'to_status' => 'converted', 'changed_by' => auth()->id()]);

        AuditService::log('QUOTATION_CONVERT', 'quotation', $quotation->id, ['invoice_id' => $invoice->id]);

        EventNotifier::quotationStatusChanged($quotation, 'converted');

        AdminNotifier::notify(
            'quotation_converted',
            'Quotation converted: '.$quotation->quotation_number,
            auth()->user()->name.' converted '.$quotation->quotation_number.' into invoice '.$invoice->invoice_number.' (invoice numbers always stay unique).',
            '/erp/invoices/'.$invoice->id,
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.invoices.show', $invoice)->with('success', 'Quotation converted to invoice.');
    }

    /**
     * Duplicate a quotation — always allocates a brand-new quotation number
     * (numbers are never reused) and flags the copy as a duplicate.
     */
    public function duplicate(Quotation $quotation)
    {
        $quotation->load('items');

        $new = Quotation::create([
            'quotation_number' => NumberSequenceService::next('quotations', 'QTN-'),
            'client_id' => $quotation->client_id,
            'duplicate_of_id' => $quotation->id,
            'currency' => $quotation->currency,
            'exchange_rate' => $quotation->exchange_rate,
            'subtotal' => $quotation->subtotal,
            'discount' => $quotation->discount,
            'tax' => $quotation->tax,
            'total' => $quotation->total,
            'status' => 'draft',
            'valid_until' => $quotation->valid_until,
            'notes' => $quotation->notes,
            'terms' => $quotation->terms,
            'created_by' => auth()->id(),
        ]);

        foreach ($quotation->items as $item) {
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
            'reason' => 'Duplicated from '.$quotation->quotation_number,
        ]);

        AuditService::log('QUOTATION_DUPLICATE', 'quotation', $new->id, [
            'duplicate_of' => $quotation->id,
            'original_number' => $quotation->quotation_number,
            'new_number' => $new->quotation_number,
        ]);

        AdminNotifier::notify(
            'quotation_duplicated',
            'Quotation duplicated: '.$new->quotation_number,
            auth()->user()->name.' duplicated '.$quotation->quotation_number.' → '.$new->quotation_number.' (new number generated).',
            '/erp/quotations/'.$new->id,
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.quotations.show', $new)
            ->with('success', "Duplicated {$quotation->quotation_number} → {$new->quotation_number} (new number generated).");
    }

    public function pdf(Quotation $quotation)
    {
        return PdfService::quotation($quotation)->stream("quotation-{$quotation->quotation_number}.pdf");
    }

    public function destroy(Quotation $quotation)
    {
        if (! in_array($quotation->status, ['draft', 'rejected', 'expired'])) {
            return back()->with('error', 'This quotation cannot be deleted.');
        }

        $quotation->delete();

        AuditService::log('QUOTATION_DELETE', 'quotation', $quotation->id, ['number' => $quotation->quotation_number]);

        AdminNotifier::notify(
            'quotation_deleted',
            'Quotation deleted: '.$quotation->quotation_number,
            auth()->user()->name.' deleted '.$quotation->quotation_number.'.',
            '/erp/quotations',
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.quotations.index')->with('success', 'Quotation deleted.');
    }
}
