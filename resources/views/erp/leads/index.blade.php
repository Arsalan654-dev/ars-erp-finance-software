@extends('erp.layouts.app')

@section('title', 'Leads Pipeline')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Leads Pipeline</h1>
        <p class="ks-page-sub">Track and manage leads through your sales funnel.</p>
    </div>
    <div class="ks-page-actions">
        @can('lead.create')
        <a href="{{ route('erp.leads.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:14px;"></i> New Lead</a>
        @endcan
    </div>
</div>

{{-- Pipeline Stats --}}
<div class="ks-stat-grid" style="grid-template-columns:repeat(7,1fr);">
    @php($colors = ['new'=>'blue','contacted'=>'yellow','follow_up'=>'orange','qualified'=>'green','quotation'=>'purple','converted'=>'green','lost'=>'red'])
    @foreach(['new','contacted','follow_up','qualified','quotation','converted','lost'] as $status)
    <a href="?status={{ $status }}" class="ks-card" style="padding:14px;text-align:center;text-decoration:none;{{ request('status')===$status ? 'border:2px solid var(--role-accent);' : '' }}">
        <div style="font-size:22px;font-weight:700;color:var(--role-accent);">{{ $counts[$status] ?? 0 }}</div>
        <div style="font-size:11px;color:var(--gray-400);text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">{{ str_replace('_', ' ', $status) }}</div>
    </a>
    @endforeach
</div>

{{-- Filters --}}
<div class="ks-card" style="margin-bottom:22px;">
    <div class="ks-card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <div style="flex:1;min-width:180px;position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search leads..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <select name="source" class="ks-form-input" style="width:150px;">
                <option value="">All Sources</option>
                @foreach(config('erp.lead_sources') as $s)
                    <option value="{{ $s }}" @selected(request('source')===$s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
            <select name="department" class="ks-form-input" style="width:150px;">
                <option value="">All Departments</option>
                @foreach(config('erp.departments') as $d)
                    <option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>
                @endforeach
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
            <a href="{{ route('erp.leads.index') }}" class="ks-btn ks-btn-light">Clear</a>
        </form>
    </div>
</div>

{{-- Leads Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Leads <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $leads->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Business / Contact</th>
                        <th>Source</th>
                        <th>Campaign</th>
                        <th>Department</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Priority</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($leads as $lead)
                    <tr>
                        <td>
                            <a href="{{ route('erp.leads.show', $lead) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $lead->business_name ?? 'N/A' }}</a>
                            @if($lead->is_duplicate)
                                <span class="ks-badge ks-badge-red" style="margin-left:6px;" title="Duplicate lead — matched against existing data">Duplicate</span>
                            @endif
                            <div style="font-size:11px;color:var(--gray-400);">{{ $lead->contact_name }}</div>
                        </td>
                        <td>{!! erp_status_badge($lead->source) !!}</td>
                        <td style="font-size:12px;color:var(--gray-400);">{{ $lead->campaign_name ?? '—' }}</td>
                        <td>{{ $lead->department }}</td>
                        <td style="text-align:center;">{!! erp_status_badge($lead->status) !!}</td>
                        <td style="text-align:center;">{!! erp_status_badge($lead->priority) !!}</td>
                        <td>{{ $lead->assignee?->name ?? '—' }}</td>
                        <td style="font-size:12px;color:var(--gray-400);">{{ $lead->created_at->diffForHumans() }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('erp.leads.show', $lead) }}" class="ks-btn ks-btn-light ks-btn-sm">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" style="text-align:center;color:var(--gray-400);padding:40px;">No leads found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $leads->links() }}
    </div>
</div>
@endsection
