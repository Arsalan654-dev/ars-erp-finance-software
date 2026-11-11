@extends('erp.layouts.app')
@section('title', 'Invoices')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Invoices</h1>
        <p class="ks-page-sub">Locked multi-currency invoices with full payment tracking.</p>
    </div>
    <div class="ks-page-actions">
        @can('invoice.create')<a href="{{ route('erp.invoices.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> New Invoice</a>@endcan
    </div>
</div>

{{-- Filters --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <div class="ks-filter-search" style="position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search number or client..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <select name="status" class="ks-form-input" style="width:170px;">
                <option value="">All Status</option>
                @foreach(['draft','sent','locked','partially_paid','paid','cancelled'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach
            </select>
            <select name="payment_status" class="ks-form-input" style="width:150px;">
                <option value="">Payment</option>
                @foreach(['unpaid','partial','paid'] as $s)<option value="{{ $s }}" @selected(request('payment_status')===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Invoices <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $invoices->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Created By (CSR)</th>
                        <th>Currency</th>
                        <th style="text-align:right;">Total</th>
                        <th style="text-align:right;">Balance</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td data-label="#"><a href="{{ route('erp.invoices.show', $inv) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $inv->invoice_number }}</a>
                                @if($inv->duplicate_of_id)
                                    <span class="ks-badge ks-badge-orange" title="Duplicated from {{ $inv->duplicateOf?->invoice_number ?? '#'.$inv->duplicate_of_id }}">Duplicate</span>
                                @endif
                                @if($inv->same_requirements ?? false)
                                    <span class="ks-badge ks-badge-orange" title="This invoice uses the same line-item wording as invoices belonging to OTHER clients — check for copied requirements.">Same requirements</span>
                                @endif
                            </td>
                            <td data-label="Client">{{ $inv->client->business_name ?? '—' }}</td>
                            <td data-label="Created By">{{ $inv->creator?->name ?? '—' }}</td>
                            <td data-label="Currency"><span class="ks-badge ks-badge-gray">{{ $inv->currency }}</span></td>
                            <td data-label="Total" style="text-align:right;" class="ks-strong">{{ erp_money($inv->total, $inv->currency) }}</td>
                            <td data-label="Balance" style="text-align:right;">{{ erp_money($inv->balance, $inv->currency) }}</td>
                            <td data-label="Status" style="text-align:center;">{!! erp_status_badge($inv->status) !!}</td>
                            <td data-label="Actions" style="text-align:right;white-space:nowrap;">
                                <a href="{{ route('erp.invoices.pdf', $inv) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> PDF</a>
                                <a href="{{ route('erp.invoices.show', $inv) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-eye" style="font-size:14px;"></i> View</a>
                                @if($inv->status === 'draft' && auth()->user()?->isSuperAdmin())
                                <form method="POST" action="{{ route('erp.invoices.destroy', $inv) }}" onsubmit="return confirm('Delete draft invoice {{ $inv->invoice_number }}?')" style="display:inline;">@csrf @method('DELETE')<button class="ks-btn ks-btn-sm" style="color:#dc2626;">Delete</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" style="text-align:center;color:var(--gray-400);padding:40px;">No invoices found.</td>
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