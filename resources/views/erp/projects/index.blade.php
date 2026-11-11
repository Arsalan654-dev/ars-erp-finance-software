@extends('erp.layouts.app')
@section('title', 'Projects')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Projects</h1>
        <p class="ks-page-sub">Track and manage all projects.</p>
    </div>
    <div class="ks-page-actions">
        @can('project.create')
        <a href="{{ route('erp.projects.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:14px;"></i> New Project</a>
        @endcan
    </div>
</div>

<div class="ks-stat-grid" style="grid-template-columns:repeat(6,1fr);">
    @foreach(['pending'=>'yellow','in_progress'=>'blue','review'=>'purple','completed'=>'green','on_hold'=>'orange'] as $status => $color)
    <a href="?status={{ $status }}" class="ks-card" style="padding:14px;text-align:center;text-decoration:none;{{ request('status')===$status ? 'border:2px solid var(--role-accent);' : '' }}">
        <div style="font-size:20px;font-weight:700;color:var(--role-accent);">{{ $counts[$status] ?? 0 }}</div>
        <div style="font-size:11px;color:var(--gray-400);text-transform:uppercase;font-weight:600;">{{ str_replace('_',' ',$status) }}</div>
    </a>
    @endforeach
</div>

<div class="ks-card" style="margin-bottom:22px;">
    <div class="ks-card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <div style="flex:1;min-width:180px;position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search projects..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <select name="department" class="ks-form-input" style="width:150px;">
                <option value="">All Departments</option>
                @foreach(config('erp.departments') as $d)
                    <option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>
                @endforeach
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
            <a href="{{ route('erp.projects.index') }}" class="ks-btn ks-btn-light">Clear</a>
        </form>
    </div>
</div>

<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Projects <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $projects->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Client</th>
                        <th>Department</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Priority</th>
                        <th>Due Date</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($projects as $project)
                    <tr>
                        <td>
                            <a href="{{ route('erp.projects.show', $project) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $project->title }}</a>
                            <div style="font-size:11px;color:var(--gray-400);">{{ $project->project_number }}</div>
                        </td>
                        <td>{{ $project->client?->business_name ?? '—' }}</td>
                        <td>{{ $project->department }}</td>
                        <td style="text-align:center;">{!! erp_status_badge($project->status) !!}</td>
                        <td style="text-align:center;">{!! erp_status_badge($project->priority) !!}</td>
                        <td style="font-size:12px;color:var(--gray-400);">{{ $project->due_date?->format('d M Y') ?? '—' }}</td>
                        <td style="text-align:center;">
                            <a href="{{ route('erp.projects.show', $project) }}" class="ks-btn ks-btn-light ks-btn-sm">View</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No projects found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $projects->links() }}
    </div>
</div>
@endsection
