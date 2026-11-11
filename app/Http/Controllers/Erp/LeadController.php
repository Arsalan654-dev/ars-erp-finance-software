<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\User;
use App\Services\AdminNotifier;
use App\Services\AuditService;
use App\Services\CsrAssigner;
use App\Services\EventNotifier;
use App\Services\LedgerService;
use App\Services\NumberSequenceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeadController extends Controller
{
    public function index(Request $request)
    {
        $query = Lead::with(['assignee', 'client', 'creator']);

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('source')) {
            $query->where('source', $request->get('source'));
        }
        if ($request->get('department')) {
            $query->where('department', $request->get('department'));
        }
        if ($request->get('q')) {
            $q = $request->get('q');
            $query->where(function ($query) use ($q) {
                $query->where('business_name', 'like', "%{$q}%")
                    ->orWhere('contact_name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('campaign_name', 'like', "%{$q}%")
                    ->orWhereHas('client', function ($cq) use ($q): void {
                        $cq->where('client_number', 'like', "%{$q}%")
                            ->orWhere('business_name', 'like', "%{$q}%");
                    });
            });
        }

        $leads = $query->latest()->paginate(15)->withQueryString();

        $counts = [
            'total' => Lead::count(),
            'new' => Lead::where('status', 'new')->count(),
            'contacted' => Lead::where('status', 'contacted')->count(),
            'follow_up' => Lead::where('status', 'follow_up')->count(),
            'qualified' => Lead::where('status', 'qualified')->count(),
            'quotation' => Lead::where('status', 'quotation')->count(),
            'converted' => Lead::where('status', 'converted')->count(),
            'lost' => Lead::where('status', 'lost')->count(),
        ];

        return view('erp.leads.index', compact('leads', 'counts'));
    }

    public function create()
    {
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('erp.leads.create', compact('users'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'source' => ['required', 'in:'.implode(',', config('erp.lead_sources'))],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'department' => ['required', 'in:'.implode(',', config('erp.departments'))],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $data['status'] = 'new';
        $data['created_by'] = auth()->id();

        $businessName = $data['business_name'] ?? $data['contact_name'] ?? null;

        // Duplicate check — match against the entire portfolio (email / phone /
        // business name) AND existing leads so repeats are always flagged.
        $client = CsrAssigner::findClient($data['email'] ?? null, $data['phone'] ?? null, $businessName);
        $duplicateLead = null;
        if ($data['email'] ?? $data['phone'] ?? $businessName) {
            $duplicateLead = Lead::query()
                ->when($data['email'] ?? null, fn ($q, $email) => $q->orWhere('email', $email))
                ->when($data['phone'] ?? null, fn ($q, $phone) => $q->orWhere('phone', $phone))
                ->when($businessName, fn ($q, $name) => $q->orWhere('business_name', $name))
                ->latest()->first();
        }
        $repeat = (bool) $client || (bool) $duplicateLead;

        if (! $client) {
            $client = Client::create([
                'business_name' => $businessName ?? 'New Lead',
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'status' => 'lead',
                'created_by' => auth()->id(),
            ]);
        }

        // Manual assignment wins; otherwise the previous CSR keeps the account,
        // falling back to the least-loaded CSR so work divides equally.
        if (empty($data['assigned_to'])) {
            $auto = CsrAssigner::resolve($client);
            $data['assigned_to'] = $auto?->id;
        }

        // Keep the client owner in sync so future repeats stay with one CSR.
        if (! $client->assigned_to && ! empty($data['assigned_to'])) {
            $client->assigned_to = $data['assigned_to'];
            $client->save();
        }

        $data['client_id'] = $client->id;
        $data['is_duplicate'] = $repeat;
        $data['duplicate_of_lead_id'] = $duplicateLead?->id;
        $lead = Lead::create($data);
        $lead->statusHistory()->create(['from_status' => null, 'to_status' => 'new', 'changed_by' => auth()->id(), 'created_at' => now()]);

        AuditService::log('LEAD_CREATE', 'lead', $lead->id, [
            'source' => $data['source'],
            'client_id' => $client->id,
            'repeat_client' => $repeat,
            'assigned_to' => $data['assigned_to'],
        ]);

        $message = $repeat
            ? 'Repeat lead matched to existing client '.$client->client_number.'.'
            : 'Lead created.';

        return redirect()->route('erp.leads.show', $lead)->with('success', $message);
    }

    public function show(Lead $lead)
    {
        $lead->load(['assignee', 'client', 'creator', 'activities.user', 'statusHistory.changedBy']);

        return view('erp.leads.show', compact('lead'));
    }

    public function edit(Lead $lead)
    {
        $users = User::where('is_active', true)->orderBy('name')->get();

        return view('erp.leads.edit', compact('lead', 'users'));
    }

    public function update(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'business_name' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email'],
            'phone' => ['nullable', 'string'],
            'source' => ['required', 'in:whatsapp,website,referral,manual,phone'],
            'campaign_name' => ['nullable', 'string', 'max:255'],
            'department' => ['required', 'in:'.implode(',', config('erp.departments'))],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'notes' => ['nullable', 'string'],
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $lead->update($data);

        return redirect()->route('erp.leads.show', $lead)->with('success', 'Lead updated.');
    }

    public function transition(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'status' => ['required', 'in:contacted,follow_up,qualified,quotation,converted,lost'],
            'reason' => ['nullable', 'string'],
            'lost_reason' => ['required_if:status,lost', 'nullable', 'string', 'max:120', 'in:'.implode(',', config('erp.lead_lost_reasons'))],
            'lost_note' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($data['status'] === 'converted') {
            return back()->with('error', 'Use the Convert to Client form — conversion requires payment details.');
        }

        if (! $lead->canTransitionTo($data['status'])) {
            return back()->with('error', "Cannot transition from '{$lead->status}' to '{$data['status']}'.");
        }

        $fromStatus = $lead->status;
        $lead->update(['status' => $data['status']]);

        if ($data['status'] === 'converted') {
            $lead->update(['converted_at' => now()]);
        }

        $historyReason = $data['reason'] ?? null;

        if ($data['status'] === 'lost') {
            // Fixed lost-reason template + free-text special note.
            $lead->update([
                'lost_reason' => $data['lost_reason'] ?? null,
                'lost_note' => $data['lost_note'] ?? null,
            ]);
            $historyReason = trim(($data['lost_reason'] ?? '').(isset($data['lost_note']) && $data['lost_note'] !== '' ? ' — '.$data['lost_note'] : ''));
            $historyReason = $historyReason !== '' ? $historyReason : null;
        }

        $lead->statusHistory()->create([
            'from_status' => $fromStatus,
            'to_status' => $data['status'],
            'changed_by' => auth()->id(),
            'reason' => $historyReason,
            'created_at' => now(),
        ]);

        AuditService::log('LEAD_STATUS_CHANGE', 'lead', $lead->id, [
            'from' => $fromStatus,
            'to' => $data['status'],
            'lost_reason' => $data['lost_reason'] ?? null,
        ]);

        if ($data['status'] === 'lost') {
            $lead->activities()->create([
                'user_id' => auth()->id(),
                'type' => 'note',
                'subject' => 'Lead marked lost: '.($data['lost_reason'] ?? 'Not specified'),
                'body' => $data['lost_note'] ?? '',
            ]);

            // Auto template (email to admins) + direct admin notification.
            EventNotifier::leadLost($lead);

            AdminNotifier::notify(
                'lead_lost',
                'Lead marked lost: '.($lead->business_name ?? $lead->contact_name ?? '#'.$lead->id),
                'Reason: '.($data['lost_reason'] ?? 'Not specified').(($data['lost_note'] ?? '') !== '' ? ' — '.$data['lost_note'] : '').' (by '.(auth()->user()->name ?? 'system').')',
                '/erp/leads/'.$lead->id,
                ['super_admin', 'csr_manager'],
                auth()->id()
            );
        } else {
            AdminNotifier::notify(
                'lead_status_change',
                'Lead moved to '.ucfirst($data['status']).': '.($lead->business_name ?? $lead->contact_name ?? '#'.$lead->id),
                'Status changed from '.ucfirst($fromStatus).' to '.ucfirst($data['status']).' by '.(auth()->user()->name ?? 'system').'.',
                '/erp/leads/'.$lead->id,
                ['super_admin'],
                auth()->id()
            );
        }

        return back()->with('success', "Lead status updated to '{$data['status']}'.");
    }

    public function convertToClient(Request $request, Lead $lead)
    {
        if ($lead->status !== 'qualified' && $lead->status !== 'quotation') {
            return back()->with('error', 'Lead must be qualified or in quotation status to convert.');
        }

        $data = $request->validate([
            'payment_type' => ['required', 'in:full,partial,special'],
            'payment_currency' => ['required', 'in:USD,GBP,EUR,PKR'],
            'payment_amount' => ['required_unless:payment_type,special', 'nullable', 'numeric', 'min:0'],
            'payment_method' => ['nullable', 'string', 'max:50'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['nullable', 'date'],
            'payment_screenshot' => ['required_unless:payment_type,special', 'nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'screenshot_amount' => ['required_unless:payment_type,special', 'nullable', 'numeric', 'min:0'],
            'special_approval_note' => ['required_if:payment_type,special', 'nullable', 'string', 'max:500'],
        ]);

        // Special approval payment type requires finance/super-admin permission
        if ($data['payment_type'] === 'special'
            && ! auth()->user()->isSuperAdmin()
            && ! auth()->user()->hasPermission('payment.verify')) {
            return back()->with('error', 'Special approval payment type requires finance permission.');
        }

        $screenshotPath = null;
        if ($request->hasFile('payment_screenshot')) {
            $screenshotPath = $request->file('payment_screenshot')->store('payment-screenshots', 'private');
        }

        // Mismatch detection: the amount declared on the screenshot vs the entered amount.
        $amountMismatch = false;
        if ($data['payment_type'] !== 'special'
            && isset($data['payment_amount'], $data['screenshot_amount'])
            && round((float) $data['payment_amount'], 2) !== round((float) $data['screenshot_amount'], 2)) {
            $amountMismatch = true;
        }

        // Update existing client (created when lead was first created) instead of creating duplicate
        $client = $lead->client;
        if ($client) {
            $client->update(['status' => 'active']);
        } else {
            // Fallback: create client if somehow none exists
            $client = Client::create([
                'business_name' => $lead->business_name ?? $lead->contact_name,
                'email' => $lead->email,
                'phone' => $lead->phone,
                'status' => 'active',
                'created_by' => auth()->id(),
            ]);
        }

        $previousStatus = $lead->status;
        $lead->update([
            'client_id' => $client->id,
            'status' => 'converted',
            'converted_at' => now(),
            'payment_type' => $data['payment_type'],
            'payment_amount' => $data['payment_amount'] ?? null,
            'payment_currency' => $data['payment_currency'],
            'payment_method' => $data['payment_method'] ?? null,
            'payment_reference' => $data['payment_reference'] ?? null,
            'payment_date' => $data['payment_date'] ?? null,
            'payment_screenshot_path' => $screenshotPath,
            'payment_approval_note' => $data['special_approval_note'] ?? null,
            'payment_approved_by' => $data['payment_type'] === 'special' ? auth()->id() : null,
        ]);

        // Record payment in Finance (pending → acknowledged → verified flow)
        $payment = null;
        if (! empty($data['payment_amount']) && $data['payment_amount'] > 0) {
            $pkr = LedgerService::toPkr($data['payment_currency'], (float) $data['payment_amount']);

            $payment = Payment::create([
                'payment_number' => NumberSequenceService::next('payments', 'PAY-'),
                'client_id' => $client->id,
                'lead_id' => $lead->id,
                'currency' => $data['payment_currency'],
                'amount' => $data['payment_amount'],
                'screenshot_amount' => $data['screenshot_amount'] ?? null,
                'amount_mismatch' => $amountMismatch,
                'exchange_rate' => $pkr['rate'],
                'pkr_equivalent' => $pkr['amount'],
                'payment_method' => $data['payment_method'] ?? null,
                'reference_number' => $data['payment_reference'] ?? null,
                'payment_date' => $data['payment_date'] ?? now()->toDateString(),
                'screenshot_path' => $screenshotPath,
                'status' => 'pending',
                'recorded_by' => auth()->id(),
                'notes' => "Lead conversion ({$data['payment_type']})",
            ]);
        }

        $lead->statusHistory()->create([
            'from_status' => $previousStatus,
            'to_status' => 'converted',
            'changed_by' => auth()->id(),
            'reason' => "Converted to Client #{$client->id}",
            'created_at' => now(),
        ]);

        $lead->activities()->create([
            'user_id' => auth()->id(),
            'type' => 'note',
            'subject' => 'Payment recorded on conversion',
            'body' => 'Payment type: '.$data['payment_type']
                .', amount: '.($data['payment_amount'] ?? 0).' '.$data['payment_currency']
                .($data['payment_type'] === 'special' ? "\nSpecial approval: ".($data['special_approval_note'] ?? '') : ''),
        ]);

        AuditService::log('LEAD_CONVERT', 'lead', $lead->id, [
            'client_id' => $client->id,
            'payment_type' => $data['payment_type'],
            'payment_amount' => $data['payment_amount'] ?? null,
            'payment_id' => $payment?->id,
        ]);

        if ($data['payment_type'] === 'special') {
            AuditService::log('PAYMENT_SPECIAL_APPROVAL', 'lead', $lead->id, [
                'note' => $data['special_approval_note'],
                'approved_by' => auth()->id(),
            ]);
        }

        // ---- Admin visibility: conversion, payment, mismatch ----
        $leadLabel = $lead->business_name ?? $lead->contact_name ?? '#'.$lead->id;

        AdminNotifier::notify(
            'lead_converted',
            'Lead converted: '.$leadLabel,
            sprintf(
                'Converted to client %s by %s. Payment type: %s%s.',
                $client->client_number,
                auth()->user()->name ?? 'system',
                $data['payment_type'],
                $payment ? ' ('.$data['payment_currency'].' '.number_format((float) $data['payment_amount'], 2).')' : ''
            ),
            '/erp/leads/'.$lead->id,
            ['super_admin'],
            auth()->id()
        );

        if ($payment) {
            // Partial / full payments are always notified to finance + admin.
            AdminNotifier::notify(
                $data['payment_type'] === 'partial' ? 'payment_partial' : 'payment_recorded',
                ucfirst($data['payment_type']).' payment recorded: '.$payment->payment_number,
                sprintf(
                    '%s %s recorded from %s by %s (lead conversion). Finance must acknowledge and verify it.',
                    $data['payment_currency'],
                    number_format((float) $data['payment_amount'], 2),
                    $leadLabel,
                    auth()->user()->name ?? 'system'
                ),
                '/erp/finance/payments/'.$payment->id,
                ['super_admin', 'finance'],
                auth()->id()
            );

            if ($amountMismatch) {
                AuditService::log('PAYMENT_AMOUNT_MISMATCH', 'payment', $payment->id, [
                    'entered' => (float) $data['payment_amount'],
                    'screenshot' => (float) $data['screenshot_amount'],
                    'lead_id' => $lead->id,
                    'recorded_by' => auth()->id(),
                ]);

                AdminNotifier::paymentMismatch(
                    $payment,
                    (float) $data['payment_amount'],
                    (float) $data['screenshot_amount'],
                    auth()->id()
                );
            }
        }

        $success = 'Lead converted to client.';
        if ($payment) {
            $success .= " Payment {$payment->payment_number} recorded (pending finance verification).";
        }
        if ($amountMismatch) {
            $success .= ' ⚠ Amount does not match the screenshot — admin has been notified.';
        }

        return redirect()->route('erp.clients.show', $client)->with('success', $success);
    }

    public function destroy(Lead $lead)
    {
        if ($lead->status === 'converted') {
            return back()->with('error', 'Converted leads cannot be deleted — they are linked to a client.');
        }

        $lead->delete();

        AuditService::log('LEAD_DELETE', 'lead', $lead->id, [
            'business_name' => $lead->business_name,
            'deleted_by' => auth()->id(),
        ]);

        AdminNotifier::notify(
            'lead_deleted',
            'Lead deleted: '.($lead->business_name ?? $lead->contact_name ?? '#'.$lead->id),
            auth()->user()->name.' deleted a lead (status: '.$lead->status.').',
            '/erp/leads',
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.leads.index')->with('success', 'Lead deleted.');
    }

    public function paymentScreenshot(Lead $lead)
    {
        abort_unless($lead->payment_screenshot_path, 404);

        $disk = Storage::disk('private');
        abort_unless($disk->exists($lead->payment_screenshot_path), 404);

        return $disk->response($lead->payment_screenshot_path, 'payment-screenshot-'.basename($lead->payment_screenshot_path));
    }

    public function storeActivity(Request $request, Lead $lead)
    {
        $data = $request->validate([
            'type' => ['required', 'in:note,call,meeting,whatsapp,email'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string'],
        ]);

        $data['user_id'] = auth()->id();
        $lead->activities()->create($data);

        return back()->with('success', 'Activity logged.');
    }
}
