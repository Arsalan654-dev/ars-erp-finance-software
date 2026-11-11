@extends('erp.layouts.app')
@section('title', 'Invoice Approvals')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Invoice Approvals</h1>
        <p class="ks-page-sub">Review and approve/reject pending invoices.</p>
    </div>
</div>

<div class="ks-stat-grid" style="grid-template-columns:repeat(3,1fr);">
    @foreach(['pending'=>'yellow','approved'=>'green','rejected'=>'red'] as $status => $color)
    <div class="ks-card" style="padding:14px;text-align:center;">
        <div style="font-size:20px;font-weight:700;color:var(--role-accent);">{{ $counts[$status] ?? 0 }}</div>
        <div style="font-size:11px;color:var(--gray-400);text-transform:uppercase;font-weight:600;">{{ ucfirst($status) }}</div>
    </div>
    @endforeach
</div>

<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">Pending Approvals <span class="ks-badge ks-badge-yellow" style="margin-left:8px;">{{ $invoices->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Client</th>
                        <th style="text-align:right;">Amount</th>
                        <th>Created By</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('erp.finance-approvals.show', $inv) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $inv->invoice_number ?? '—' }}</a>
                        </td>
                        <td>{{ $inv->client?->business_name ?? '—' }}</td>
                        <td style="text-align:right;">{{ erp_money($inv->total ?? 0, 'PKR') }}</td>
                        <td>{{ $inv->creator?->name ?? '—' }}</td>
                        <td style="text-align:center;">{!! erp_status_badge($inv->latestApproval?->status ?? 'pending') !!}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('erp.finance-approvals.show', $inv) }}" class="ks-btn ks-btn-light ks-btn-sm">Review</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">No pending approvals.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $invoices->links() }}
    </div>
</div>
@endsection
