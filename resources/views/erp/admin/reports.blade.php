@extends('erp.layouts.app')
@section('title', 'Admin Reports')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Admin Reports</h1>
        <p class="ks-page-sub">Cross-module operational overview — flags that need an owner's attention.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.admin.work-report') }}" class="ks-btn ks-btn-light"><i class="ti ti-users" style="font-size:14px;"></i> Work Report</a>
        <a href="{{ route('erp.admin.audit-logs') }}" class="ks-btn ks-btn-light"><i class="ti ti-history" style="font-size:14px;"></i> Audit Logs</a>
        <a href="{{ route('erp.finance.reports') }}" class="ks-btn ks-btn-light"><i class="ti ti-report" style="font-size:14px;"></i> Financial Reports</a>
    </div>
</div>

{{-- Counts --}}
<div class="ks-stat-grid" style="grid-template-columns:repeat(4,1fr);">
    @php($cards = [
        ['leads', 'Leads', ''],
        ['leads_follow_up', 'Leads in Follow-up', ''],
        ['leads_lost', 'Leads Lost', ''],
        ['clients', 'Clients', ''],
        ['quotations', 'Quotations', ''],
        ['quotations_duplicate', 'Duplicate Quotations', $counts['quotations_duplicate'] > 0 ? 'flag' : ''],
        ['invoices', 'Invoices', ''],
        ['invoice_descriptions_shared', 'Shared Invoice Requirements', $counts['invoice_descriptions_shared'] > 0 ? 'flag' : ''],
        ['payments', 'Payments', ''],
        ['payments_flagged', '⚠ Mismatch Payments', $counts['payments_flagged'] > 0 ? 'flag' : ''],
        ['payments_pending', 'Payments Awaiting Action', ''],
        ['tasks', 'Tasks', ''],
        ['users', 'Users', ''],
    ])
    @foreach($cards as [$key, $label, $tone])
    <div class="ks-card" style="padding:14px;text-align:center;{{ $tone === 'flag' ? 'border:2px solid #dc2626;' : '' }}">
        <div style="font-size:22px;font-weight:700;color:{{ $tone === 'flag' ? '#dc2626' : 'var(--role-accent)' }};">{{ $counts[$key] ?? 0 }}</div>
        <div style="font-size:11px;color:var(--gray-400);text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Flagged payments --}}
<div class="ks-card" style="margin-top:22px;">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">⚠ Payments with Screenshot Mismatch <span class="ks-badge ks-badge-red" style="margin-left:8px;">{{ $flaggedPayments->count() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr><th>#</th><th>Client</th><th style="text-align:right;">Recorded</th><th style="text-align:right;">On Screenshot</th><th>Recorded By</th><th>Date</th></tr>
                </thead>
                <tbody>
                    @forelse($flaggedPayments as $fp)
                    <tr>
                        <td><a href="{{ route('erp.finance.payments.show', $fp) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $fp->payment_number }}</a></td>
                        <td>{{ $fp->client->business_name ?? '—' }}</td>
                        <td style="text-align:right;">{{ erp_money($fp->amount, $fp->currency) }}</td>
                        <td style="text-align:right;color:#dc2626;font-weight:600;">{{ $fp->screenshot_amount !== null ? erp_money($fp->screenshot_amount, $fp->currency) : '—' }}</td>
                        <td>{{ $fp->recorder->name ?? '—' }}</td>
                        <td>{{ $fp->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:30px;">No mismatched payments. 🎯</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Duplicate quotations --}}
<div class="ks-card" style="margin-top:22px;">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">Duplicate Quotations <span class="ks-badge ks-badge-orange" style="margin-left:8px;">{{ $duplicateQuotations->count() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr><th>#</th><th>Duplicate Of</th><th>Client</th><th style="text-align:right;">Amount</th><th>Status</th><th>Created</th></tr>
                </thead>
                <tbody>
                    @forelse($duplicateQuotations as $dq)
                    <tr>
                        <td><a href="{{ route('erp.quotations.show', $dq) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $dq->quotation_number }}</a></td>
                        <td>{{ $dq->duplicateOf?->quotation_number ?? '#'.$dq->duplicate_of_id }}</td>
                        <td>{{ $dq->client->business_name ?? '—' }}</td>
                        <td style="text-align:right;">{{ erp_money($dq->total, $dq->currency) }}</td>
                        <td>{!! erp_status_badge($dq->status) !!}</td>
                        <td>{{ $dq->created_at->format('d M Y H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:30px;">No duplicated quotations.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
