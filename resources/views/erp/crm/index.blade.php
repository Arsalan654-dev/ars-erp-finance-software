@extends('erp.layouts.app')
@section('title', 'Clients')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Clients</h1>
        <p class="ks-page-sub">Search your portfolio by name, email, phone, EIN or UTR.</p>
    </div>
    <div class="ks-page-actions">
        @can('client.create')
            <a href="{{ route('erp.clients.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> New Client</a>
        @endcan
    </div>
</div>

{{-- Search card --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <div class="ks-filter-search" style="position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search by name, email, phone, EIN, UTR..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <select name="status" class="ks-form-input" style="max-width:160px;">
                <option value="">All Status</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
                <option value="lead" @selected(request('status') === 'lead')>Lead</option>
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Search</button>
            @if(request()->anyFilled(['q','status']))
                <a href="{{ route('erp.clients.index') }}" class="ks-btn ks-btn-light">Clear</a>
            @endif
        </form>
    </div>
</div>

{{-- Table card --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Clients <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $clients->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Client #</th>
                        <th>Business Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Country</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($clients as $c)
                        <tr>
                            <td data-label="Client #"><span class="ks-badge ks-badge-gray">{{ $c->client_number ?? '—' }}</span></td>
                            <td data-label="Business"><a href="{{ route('erp.clients.show', $c) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $c->business_name }}</a></td>
                            <td data-label="Email">{{ $c->email ?? '—' }}</td>
                            <td data-label="Phone">{{ $c->phone ?? '—' }}</td>
                            <td data-label="Country">{{ $c->country ?? '—' }}</td>
                            <td data-label="Status" style="text-align:center;">{!! erp_status_badge($c->status) !!}</td>
                            <td data-label="Actions" style="text-align:right;">
                                <a href="{{ route('erp.clients.show', $c) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-eye" style="font-size:14px;"></i> View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No clients found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $clients->links() }}
    </div>
</div>
@endsection
