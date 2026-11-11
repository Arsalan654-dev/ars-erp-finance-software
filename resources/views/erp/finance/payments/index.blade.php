@extends('erp.layouts.app')
@section('title', 'Payments')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Payments</h1>
        <p class="ks-page-sub">Multi-currency payment records with bank verification and ledger posting.</p>
    </div>
    <div class="ks-page-actions">
        @can('payment.create')
        <a href="{{ route('erp.finance.payments.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> Record Payment</a>
        @endcan
    </div>
</div>

{{-- Filters --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <div class="ks-filter-search" style="position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search number, client, reference..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <select name="status" class="ks-form-input" style="width:150px;">
                <option value="">All Status</option>
                @foreach(['pending','acknowledged','verified','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <label class="flex items-center gap-2 text-sm" style="color:var(--gray-600);">
                <input type="checkbox" name="flagged" value="1" @checked(request('flagged') === '1')> Mismatch only
                @if(($flaggedCount ?? 0) > 0)
                    <span class="ks-badge ks-badge-red">{{ $flaggedCount }}</span>
                @endif
            </label>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
            @if(request('flagged') || request('q') || request('status'))
                <a href="{{ route('erp.finance.payments') }}" class="ks-btn ks-btn-light">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Payments <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $payments->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Currency</th>
                        <th style="text-align:right;">Amount</th>
                        <th style="text-align:right;">PKR Equiv.</th>
                        <th>Date</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $p)
                        <tr>
                            <td><a href="{{ route('erp.finance.payments.show', $p) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $p->payment_number }}</a>
                                @if($p->amount_mismatch)
                                    <span class="ks-badge ks-badge-red" title="Amount entered differs from the amount visible on the screenshot — reported to admin.">Mismatch</span>
                                @endif
                            </td>
                            <td>{{ $p->client->business_name ?? '—' }}</td>
                            <td><span class="ks-badge ks-badge-gray">{{ $p->currency }}</span></td>
                            <td style="text-align:right;" class="ks-strong">{{ erp_money($p->amount, $p->currency) }}</td>
                            <td style="text-align:right;">{{ erp_money($p->pkr_equivalent, 'PKR') }}</td>
                            <td>{{ $p->payment_date?->format('Y-m-d') }}</td>
                            <td style="text-align:center;">{!! erp_status_badge($p->status) !!}</td>
                            <td style="text-align:right;">
                                <a href="{{ route('erp.finance.payments.receipt', $p) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> PDF</a>
                                <a href="{{ route('erp.finance.payments.show', $p) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-eye" style="font-size:14px;"></i> View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:var(--gray-400);padding:40px;">No payments recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $payments->links() }}
    </div>
</div>
@endsection