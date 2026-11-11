@extends('erp.layouts.app')
@section('title', 'Work Report')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Work Report</h1>
        <p class="ks-page-sub">Per-person workload — what everyone did, when, and what is still remaining.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.admin.reports') }}" class="ks-btn ks-btn-light"><i class="ti ti-report" style="font-size:14px;"></i> Admin Reports</a>
        <a href="{{ route('erp.admin.audit-logs') }}" class="ks-btn ks-btn-light"><i class="ti ti-history" style="font-size:14px;"></i> Audit Logs</a>
    </div>
</div>

{{-- Date range filter --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <label class="ks-label" style="margin:0;">From</label>
            <input type="date" name="from" value="{{ $from?->format('Y-m-d') }}" class="ks-form-input" style="width:160px;">
            <label class="ks-label" style="margin:0;">To</label>
            <input type="date" name="to" value="{{ $to?->format('Y-m-d') }}" class="ks-form-input" style="width:160px;">
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
            <a href="{{ route('erp.admin.work-report') }}" class="ks-btn ks-btn-light">Clear</a>
        </form>
    </div>
</div>

{{-- Period totals --}}
<div class="ks-stat-grid" style="grid-template-columns:repeat(5,1fr);">
    @php($periodCards = [
        ['tasks_completed', 'Tasks Completed'],
        ['leads_created', 'Leads Created'],
        ['quotations_created', 'Quotations Created'],
        ['invoices_created', 'Invoices Created'],
        ['payments_recorded', 'Payments Recorded'],
    ])
    @foreach($periodCards as [$key, $label])
    <div class="ks-card" style="padding:14px;text-align:center;">
        <div style="font-size:22px;font-weight:700;color:var(--role-accent);">{{ $periodCounts[$key] ?? 0 }}</div>
        <div style="font-size:11px;color:var(--gray-400);text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">{{ $label }}</div>
    </div>
    @endforeach
</div>

{{-- Per-person table --}}
<div class="ks-card" style="margin-top:22px;">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">Per-Person Work Report <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $rows->count() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Person</th>
                        <th style="text-align:center;">Tasks Completed</th>
                        <th style="text-align:center;">Tasks Created</th>
                        <th style="text-align:center;">Leads (Created / Assigned)</th>
                        <th style="text-align:center;">Leads Converted</th>
                        <th style="text-align:center;">Quotations</th>
                        <th style="text-align:center;">Invoices</th>
                        <th style="text-align:center;">Payments</th>
                        <th style="text-align:center;">Work Done</th>
                        <th style="text-align:center;">Remaining (Open Tasks)</th>
                        <th>Last Activity</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td>
                            <span class="ks-strong">{{ $row['user']->name }}</span>
                            <div style="font-size:11px;color:var(--gray-400);">{{ $row['user']->email }}</div>
                        </td>
                        <td style="text-align:center;">{{ $row['completed_tasks'] }}</td>
                        <td style="text-align:center;">{{ $row['created_tasks'] }}</td>
                        <td style="text-align:center;">{{ $row['leads'] }} / {{ $row['leads_assigned'] }}</td>
                        <td style="text-align:center;">{{ $row['leads_converted'] }}</td>
                        <td style="text-align:center;">{{ $row['quotations'] }}</td>
                        <td style="text-align:center;">{{ $row['invoices'] }}</td>
                        <td style="text-align:center;">{{ $row['payments'] }}</td>
                        <td style="text-align:center;" class="ks-strong">{{ $row['work_done'] }}</td>
                        <td style="text-align:center;">
                            @if($row['remaining'] > 0)
                                <span class="ks-badge ks-badge-yellow">{{ $row['remaining'] }}</span>
                            @else
                                <span class="ks-badge ks-badge-green">0</span>
                            @endif
                        </td>
                        <td style="font-size:12px;color:var(--gray-400);">{{ $row['last_action_at']?->diffForHumans() ?? '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="11" style="text-align:center;color:var(--gray-400);padding:40px;">No users found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
