<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\InvoiceApproval;
use App\Services\AuditService;
use Illuminate\Http\Request;

class FinanceApprovalController extends Controller
{
    public function index(Request $request)
    {
        $query = Invoice::with(['client', 'creator', 'latestApproval']);

        if ($request->get('status')) {
            if ($request->get('status') === 'pending') {
                $query->whereDoesntHave('latestApproval', function ($q) {
                    $q->whereIn('status', ['approved', 'rejected']);
                })->where('status', 'locked');
            } else {
                $query->whereHas('latestApproval', function ($q) use ($request) {
                    $q->where('status', $request->get('status'));
                });
            }
        }

        $invoices = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'pending' => Invoice::where('status', 'locked')
                ->whereDoesntHave('latestApproval', function ($q) {
                    $q->whereIn('status', ['approved', 'rejected']);
                })->count(),
            'approved' => InvoiceApproval::where('status', 'approved')->count(),
            'rejected' => InvoiceApproval::where('status', 'rejected')->count(),
        ];

        return view('erp.finance-approvals.index', compact('invoices', 'counts'));
    }

    public function approve(Invoice $invoice, Request $request)
    {
        if ($invoice->status !== 'locked') {
            return back()->with('error', 'Only locked invoices can be approved.');
        }

        $existing = InvoiceApproval::where('invoice_id', $invoice->id)->whereIn('status', ['approved', 'rejected'])->first();
        if ($existing) {
            return back()->with('error', 'This invoice already has a decision.');
        }

        $data = $request->validate(['notes' => ['nullable', 'string']]);

        InvoiceApproval::create([
            'invoice_id' => $invoice->id,
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'notes' => $data['notes'] ?? null,
            'decided_at' => now(),
        ]);

        AuditService::log('INVOICE_APPROVE', 'invoice', $invoice->id, ['by' => auth()->id()]);

        return back()->with('success', 'Invoice approved.');
    }

    public function reject(Invoice $invoice, Request $request)
    {
        if ($invoice->status !== 'locked') {
            return back()->with('error', 'Only locked invoices can be rejected.');
        }

        $existing = InvoiceApproval::where('invoice_id', $invoice->id)->whereIn('status', ['approved', 'rejected'])->first();
        if ($existing) {
            return back()->with('error', 'This invoice already has a decision.');
        }

        $data = $request->validate(['notes' => ['required', 'string']]);

        InvoiceApproval::create([
            'invoice_id' => $invoice->id,
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'notes' => $data['notes'],
            'decided_at' => now(),
        ]);

        $invoice->update(['status' => 'draft']);

        AuditService::log('INVOICE_REJECT', 'invoice', $invoice->id, ['by' => auth()->id(), 'reason' => $data['notes']]);

        return back()->with('success', 'Invoice rejected and returned to draft.');
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['client', 'items', 'creator', 'latestApproval.approvedBy', 'latestApproval.rejectedBy']);

        return view('erp.finance-approvals.show', compact('invoice'));
    }
}
