@extends('erp.layouts.app')
@section('title', 'Tasks')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">All Tasks</h1>
        <p class="ks-page-sub">Manage operational tasks across US Tax, UK Tax and Legal departments.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.tasks.board') }}" class="ks-btn ks-btn-light"><i class="ti ti-layout-kanban" style="font-size:15px;"></i> Kanban Board</a>
        @can('task.create')<a href="{{ route('erp.tasks.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> New Task</a>@endcan
    </div>
</div>

{{-- Filters --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <div class="ks-filter-search" style="position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tasks..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <select name="status" class="ks-form-input" style="width:150px;">
                <option value="">All Status</option>
                @foreach(config('erp.task_statuses') as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach
            </select>
            <select name="department" class="ks-form-input" style="width:150px;">
                <option value="">All Depts</option>
                @foreach(['US TAX','UK TAX','LEGAL'] as $d)<option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>@endforeach
            </select>
            <select name="priority" class="ks-form-input" style="width:130px;">
                <option value="">All Priority</option>
                @foreach(['low','medium','high','urgent'] as $pr)<option value="{{ $pr }}" @selected(request('priority')===$pr)>{{ ucfirst($pr) }}</option>@endforeach
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Tasks <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $tasks->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Client</th>
                        <th>Dept</th>
                        <th>Assignees</th>
                        <th>Due</th>
                        <th style="text-align:center;">Priority</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $t)
                        <tr>
                            <td data-label="#"><a href="{{ route('erp.tasks.show', $t) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $t->task_number }}</a></td>
                            <td data-label="Title">{{ $t->title }}</td>
                            <td data-label="Client">{{ $t->client->business_name ?? '—' }}</td>
                            <td data-label="Dept">{{ $t->department }}</td>
                            <td data-label="Assignees">{{ $t->assignees->pluck('name')->implode(', ') ?: '—' }}</td>
                            <td data-label="Due" @if($t->isOverdue()) style="color:#dc2626;font-weight:600;" @endif>{{ $t->due_date?->format('Y-m-d') }} @if($t->isOverdue()) ⚠️ @endif</td>
                            <td data-label="Priority" style="text-align:center;">{!! erp_status_badge($t->priority) !!}</td>
                            <td data-label="Status" style="text-align:center;">{!! erp_status_badge($t->status) !!}</td>
                            <td data-label="Actions" style="text-align:right;">
                                <a href="{{ route('erp.tasks.show', $t) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-eye" style="font-size:14px;"></i> View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" style="text-align:center;color:var(--gray-400);padding:40px;">No tasks found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $tasks->links() }}
    </div>
</div>
@endsection