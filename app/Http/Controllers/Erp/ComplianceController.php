<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\ComplianceRecord;
use App\Models\Invoice;
use App\Services\AuditService;
use App\Services\DocumentService;
use App\Services\NumberSequenceService;
use Illuminate\Http\Request;

class ComplianceController extends Controller
{
    public function index(Request $request)
    {
        $query = ComplianceRecord::with('client');

        $status = $request->get('status');
        if ($status === 'overdue') {
            $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
        } elseif ($status === 'expiring_30') {
            $query->whereNotNull('expiry_date')->whereBetween('expiry_date', [now(), now()->addDays(30)]);
        } elseif ($status === 'expiring_60') {
            $query->whereNotNull('expiry_date')->whereBetween('expiry_date', [now(), now()->addDays(60)]);
        } elseif ($status) {
            $query->where('status', $status);
        }

        $records = $query->latest()->paginate(15)->withQueryString();

        $summary = [
            'healthy' => ComplianceRecord::where(function ($q) {
                $q->whereNull('expiry_date')->orWhere('expiry_date', '>', now()->addDays(60));
            })->count(),
            'due_60' => ComplianceRecord::whereNotNull('expiry_date')->whereBetween('expiry_date', [now(), now()->addDays(60)])->count(),
            'due_30' => ComplianceRecord::whereNotNull('expiry_date')->whereBetween('expiry_date', [now(), now()->addDays(30)])->count(),
            'overdue' => ComplianceRecord::whereNotNull('expiry_date')->where('expiry_date', '<', now())->count(),
        ];

        return view('erp.compliance.index', compact('records', 'summary'));
    }

    public function create()
    {
        $clients = \App\Models\Client::where('status', 'active')->orderBy('business_name')->get();

        return view('erp.compliance.create', compact('clients'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'document_type' => ['required', 'string'],
            'title' => ['nullable', 'string'],
            'reference_number' => ['nullable', 'string'],
            'issuing_authority' => ['nullable', 'string'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ]);

        // Validate PDF upload BEFORE creating the record
        if ($request->hasFile('document_file')) {
            $request->validate([
                'document_file' => ['nullable', 'file', 'mimes:pdf', 'max:20480'],
            ]);
        }

        $data['status'] = $data['expiry_date'] && $data['expiry_date'] < now() ? 'expired' : 'active';
        $data['created_by'] = auth()->id();

        $record = null;
        \Illuminate\Support\Facades\DB::transaction(function () use ($data, $request, &$record) {
            $record = ComplianceRecord::create($data);

            // Attach an uploaded document if provided — PDF only
            if ($request->hasFile('document_file')) {
                DocumentService::store(
                    $request->file('document_file'),
                    $data['client_id'],
                    $record->id,
                    $data['document_type'],
                    $data['title']
                );
            }
        });

        AuditService::log('COMPLIANCE_CREATE', 'compliance', $record->id, ['type' => $data['document_type']]);

        return redirect()->route('erp.compliance.show', $record)->with('success', 'Compliance record created.');
    }

    public function show(ComplianceRecord $record)
    {
        $record->load(['client', 'documents', 'renewalEvents']);

        return view('erp.compliance.show', compact('record'));
    }

    public function edit(ComplianceRecord $record)
    {
        $clients = \App\Models\Client::where('status', 'active')->orderBy('business_name')->get();

        return view('erp.compliance.edit', compact('record', 'clients'));
    }

    public function update(Request $request, ComplianceRecord $record)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'document_type' => ['required', 'string'],
            'title' => ['nullable', 'string'],
            'reference_number' => ['nullable', 'string'],
            'issuing_authority' => ['nullable', 'string'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
        ]);

        $record->update($data);

        return redirect()->route('erp.compliance.show', $record)->with('success', 'Compliance record updated.');
    }

    /**
     * One-click renewal: mark renewed + create a renewal invoice.
     */
    public function renew(Request $request, ComplianceRecord $record)
    {
        $amount = $request->input('amount', 0);
        $currency = $request->input('currency', 'USD');

        $record->update(['status' => 'renewed']);

        if ($amount > 0) {
            $invoice = Invoice::create([
                'invoice_number' => NumberSequenceService::next('invoices', 'INV-'),
                'client_id' => $record->client_id,
                'currency' => $currency,
                'total' => $amount,
                'balance' => $amount,
                'subtotal' => $amount,
                'status' => 'draft',
                'payment_status' => 'unpaid',
                'issue_date' => now(),
                'created_by' => auth()->id(),
            ]);

            $invoice->items()->create([
                'description' => 'Renewal - '.$record->document_type.' ('.$record->title.')',
                'quantity' => 1,
                'unit_price' => $amount,
                'total' => $amount,
            ]);

            $record->renewalEvents()->create([
                'event_type' => 'renewal_invoice',
                'metadata' => ['invoice_id' => $invoice->id, 'amount' => $amount, 'currency' => $currency],
            ]);

            // Send RENEWAL_INVOICE email to client
            $record->loadMissing('client');
            if ($record->client?->email) {
                \App\Services\EventNotifier::complianceReminder([
                    'id' => $record->id,
                    'client' => [
                        'email' => $record->client->email,
                        'business_name' => $record->client->business_name,
                    ],
                    'document_type' => $record->document_type,
                    'reference' => $record->reference_number ?? $record->title,
                    'expiry_date' => $record->expiry_date?->toDateString(),
                ], 'RENEWAL_INVOICE', ['invoice_number' => $invoice->invoice_number, 'amount' => number_format($amount, 2), 'currency' => $currency]);
            }

            AuditService::log('COMPLIANCE_RENEW', 'compliance', $record->id, ['invoice_id' => $invoice->id]);

            return redirect()->route('erp.invoices.show', $invoice)->with('success', 'Record renewed. Invoice created.');
        }

        $record->renewalEvents()->create(['event_type' => 'renewal_invoice']);

        return back()->with('success', 'Compliance record renewed.');
    }

    public function destroy(ComplianceRecord $record)
    {
        $record->delete();

        return redirect()->route('erp.compliance.index')->with('success', 'Compliance record deleted.');
    }
}