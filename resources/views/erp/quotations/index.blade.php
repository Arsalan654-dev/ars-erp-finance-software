@extends('erp.layouts.app')
@section('title', 'Quotations')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Quotations</h1>
        <p class="ks-page-sub">Build, send and convert quotations to locked invoices.</p>
    </div>
    <div class="ks-page-actions">
        @can('quotation.create')<a href="{{ route('erp.quotations.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> New Quotation</a>@endcan
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
                @foreach(['draft','sent','accepted','rejected','expired','converted'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Quotations <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $quotations->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Created By (CSR)</th>
                        <th style="text-align:right;">Amount</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotations as $q)
                        <tr>
                            <td data-label="#"><a href="{{ route('erp.quotations.show', $q) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $q->quotation_number }}</a>
                                @if($q->duplicate_of_id)
                                    <span class="ks-badge ks-badge-orange" title="Duplicated from {{ $q->duplicateOf?->quotation_number ?? '#'.$q->duplicate_of_id }}">Duplicate</span>
                                @endif
                            </td>
                            <td data-label="Client">{{ $q->client->business_name ?? '—' }}</td>
                            <td data-label="Date" style="color:var(--gray-500);">{{ $q->created_at->format('M d, Y') }}</td>
                            <td data-label="Created By">{{ $q->creator?->name ?? '—' }}</td>
                            <td data-label="Amount" style="text-align:right;" class="ks-strong">{{ erp_money($q->total, $q->currency) }}</td>
                            <td data-label="Status" style="text-align:center;">{!! erp_status_badge($q->status) !!}</td>
                            <td data-label="Actions" style="text-align:right;">
                                <a href="{{ route('erp.quotations.pdf', $q) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> PDF</a>
                                <a href="{{ route('erp.quotations.show', $q) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-eye" style="font-size:14px;"></i> View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No quotations found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $quotations->links() }}
    </div>
</div>
@endsection