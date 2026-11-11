@extends('erp.layouts.app')
@section('title', 'Compliance')
@section('content')

@php
    $iconCheck = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $iconClock = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $iconAlert = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
    $iconShield = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
@endphp

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Compliance Vault</h1>
        <p class="ks-page-sub">Track document renewals with automated 60/30/due alerts and one-click renewal invoicing.</p>
    </div>
    <div class="ks-page-actions">
        @can('compliance.create')
        <a href="{{ route('erp.compliance.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> New Record</a>
        @endcan
    </div>
</div>

{{-- Summary stats --}}
<div class="ks-stat-grid">
    <x-stat-card label="Healthy" value="{{ $summary['healthy'] }}" color="green" icon="{!! $iconCheck !!}" />
    <x-stat-card label="Due < 60 days" value="{{ $summary['due_60'] }}" color="yellow" icon="{!! $iconClock !!}" />
    <x-stat-card label="Due < 30 days" value="{{ $summary['due_30'] }}" color="orange" icon="{!! $iconAlert !!}" />
    <x-stat-card label="Overdue" value="{{ $summary['overdue'] }}" color="red" icon="{!! $iconShield !!}" />
</div>

{{-- Filters --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <select name="status" class="ks-form-input" style="width:220px;">
                <option value="">All</option>
                <option value="overdue" @selected(request('status')==='overdue')>Overdue</option>
                <option value="expiring_60" @selected(request('status')==='expiring_60')>Expiring < 60 days</option>
                <option value="expiring_30" @selected(request('status')==='expiring_30')>Expiring < 30 days</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="expired" @selected(request('status')==='expired')>Expired</option>
                <option value="renewed" @selected(request('status')==='renewed')>Renewed</option>
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">Compliance Records <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $records->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Client</th>
                        <th>Type</th>
                        <th>Reference</th>
                        <th>Expiry</th>
                        <th style="text-align:center;">Days Left</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($records as $r)
                        <tr>
                            <td>{{ $r->client->business_name ?? '—' }}</td>
                            <td><a href="{{ route('erp.compliance.show', $r) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $r->document_type }}</a></td>
                            <td>{{ $r->reference_number }}</td>
                            <td>{{ $r->expiry_date?->format('Y-m-d') }}</td>
                            <td style="text-align:center;">
                                @if($r->expiry_date)
                                    @if($r->expiry_date->isPast())
                                        <span class="ks-badge ks-badge-red">Overdue</span>
                                    @elseif($r->expiry_date->diffInDays(now()) <= 30)
                                        <span class="ks-badge ks-badge-orange">{{ $r->expiry_date->diffInDays(now()) }} days</span>
                                    @else
                                        <span class="ks-badge ks-badge-gray">{{ $r->expiry_date->diffInDays(now()) }} days</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td style="text-align:center;">{!! erp_status_badge($r->status) !!}</td>
                            <td style="text-align:right;">
                                <a href="{{ route('erp.compliance.show', $r) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-eye" style="font-size:14px;"></i> View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No compliance records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $records->links() }}
    </div>
</div>
@endsection