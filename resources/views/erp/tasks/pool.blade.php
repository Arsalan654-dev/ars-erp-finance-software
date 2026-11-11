@extends('erp.layouts.app')
@section('title', 'Task Distribution Pool')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">CSR Task Distribution Pool</h1>
        <p class="ks-page-sub">Centralized queue of unassigned tasks — claim and distribute work.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.tasks.index') }}" class="ks-btn ks-btn-light"><i class="ti ti-list" style="font-size:15px;"></i> All Tasks</a>
        @can('task.create')<a href="{{ route('erp.tasks.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> New Task</a>@endcan
    </div>
</div>

{{-- Stats cards --}}
<div class="ks-stat-grid">
    <x-stat-card label="Unassigned" value="{{ $stats['total'] }}" color="indigo" icon="{!! $iconTasks !!}" />
    <x-stat-card label="Urgent" value="{{ $stats['urgent'] }}" color="red" icon="{!! $iconAlert !!}" />
    <x-stat-card label="High" value="{{ $stats['high'] }}" color="orange" icon="{!! $iconTrendUp !!}" />
    <x-stat-card label="Medium" value="{{ $stats['medium'] }}" color="yellow" icon="{!! $iconClock !!}" />
</div>

{{-- Filters --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <select name="department" class="ks-form-input" style="width:170px;">
                <option value="">All Departments</option>
                @foreach(['US TAX','UK TAX','LEGAL'] as $d)<option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>@endforeach
            </select>
            <select name="priority" class="ks-form-input" style="width:150px;">
                <option value="">All Priorities</option>
                @foreach(['low','medium','high','urgent'] as $p)<option value="{{ $p }}" @selected(request('priority')===$p)>{{ ucfirst($p) }}</option>@endforeach
            </select>
            <div style="flex:1;min-width:200px;position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search tasks..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
        </form>
    </div>
</div>

{{-- Pool Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">Unassigned Tasks <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $tasks->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Task</th>
                        <th>Client</th>
                        <th>Department</th>
                        <th style="text-align:center;">Priority</th>
                        <th>Created</th>
                        <th style="text-align:center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td data-label="Task">
                                <span class="ks-list-sub">{{ $task->task_number }}</span>
                                <a href="{{ route('erp.tasks.show', $task) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $task->title }}</a>
                            </td>
                            <td data-label="Client">{{ $task->client->business_name ?? '—' }}</td>
                            <td data-label="Department">{{ $task->department ?? '—' }}</td>
                            <td data-label="Priority" style="text-align:center;">{!! erp_status_badge($task->priority) !!}</td>
                            <td data-label="Created">{{ $task->created_at->format('d M') }}</td>
                            <td data-label="Action" style="text-align:center;">
                                @can('task.assign')
                                <form method="POST" action="{{ route('erp.tasks.claim', $task) }}" onsubmit="return confirm('Claim this task?')">
                                    @csrf
                                    <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm"><i class="ti ti-shield-lock" style="font-size:14px;"></i> Claim</button>
                                </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">No unassigned tasks in the pool. 🎉</td>
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