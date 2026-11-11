@extends('erp.layouts.app')
@section('title', 'Project: ' . $project->title)
@section('content')

<div class="ks-page-header">
    <div>
        <a href="{{ route('erp.projects.index') }}" style="font-size:12px;color:var(--gray-400);text-decoration:none;">← Back to Projects</a>
        <h1 class="ks-page-title">{{ $project->title }}</h1>
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            <span style="font-size:12px;color:var(--gray-400);">{{ $project->project_number }}</span>
            {!! erp_status_badge($project->status) !!}
            <span style="font-size:13px;color:var(--gray-400);">{{ $project->department }} · {{ $project->priority }}</span>
        </div>
    </div>
    <div class="ks-page-actions">
        @if(!in_array($project->status, ['completed','cancelled']))
        @can('project.update')
        <a href="{{ route('erp.projects.edit', $project) }}" class="ks-btn ks-btn-light"><i class="ti ti-edit" style="font-size:14px;"></i> Edit</a>
        @endcan
        @endif
    </div>
</div>

<div class="ks-grid-3" style="grid-template-columns:2fr 1fr;gap:22px;">
    <div class="space-y-4">
        {{-- Details --}}
        <div class="ks-card">
            <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Project Details</p></div>
            <div class="ks-card-body">
                <div class="grid grid-cols-2 gap-3" style="font-size:13px;">
                    <div><span style="color:var(--gray-400);">Client:</span> {{ $project->client?->business_name ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Invoice:</span> {{ $project->invoice?->invoice_number ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Start:</span> {{ $project->start_date?->format('d M Y') ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Due:</span> {{ $project->due_date?->format('d M Y') ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Created By:</span> {{ $project->assignedBy?->name ?? '—' }}</div>
                </div>
                @if($project->description)
                <div style="margin-top:12px;padding:10px 14px;background:var(--gray-50);border-radius:8px;font-size:13px;color:var(--gray-600);">{{ $project->description }}</div>
                @endif
            </div>
        </div>

        {{-- Status Transition --}}
        @if(!in_array($project->status, ['completed','cancelled']))
        <div class="ks-card">
            <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Move Status</p></div>
            <div class="ks-card-body">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @php($transitions = ['pending'=>['in_progress','on_hold'],'in_progress'=>['review','on_hold'],'review'=>['completed','in_progress'],'on_hold'=>['pending','in_progress']])
                    @can('project.update')
                    @foreach($transitions[$project->status] ?? [] as $next)
                    <form method="POST" action="{{ route('erp.projects.transition', $project) }}">
                        @csrf
                        <input type="hidden" name="status" value="{{ $next }}">
                        <button class="ks-btn ks-btn-light ks-btn-sm">→ {{ ucfirst(str_replace('_',' ',$next)) }}</button>
                    </form>
                    @endforeach
                    @endcan
                </div>
            </div>
        </div>
        @endif

        {{-- Members --}}
        <div class="ks-card">
            <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Team Members</p></div>
            <div class="ks-card-body-0">
                @if($project->members->count())
                <div class="ks-table-wrap">
                    <table class="ks-table ks-table-responsive">
                        <thead><tr><th>User</th><th style="text-align:center;">Role</th><th style="text-align:right;">Action</th></tr></thead>
                        <tbody>
                            @foreach($project->members as $m)
                            <tr>
                                <td class="ks-strong">{{ $m->user?->name ?? '—' }}</td>
                                <td style="text-align:center;">{!! erp_status_badge($m->role_in_project) !!}</td>
                                <td style="text-align:right;">
                                    @can('project.assign')
                                    <form method="POST" action="{{ route('erp.projects.members.destroy', [$project, $m]) }}" class="inline" onsubmit="return confirm('Remove?')">
                                        @csrf @method('DELETE')
                                        <button class="ks-btn ks-btn-light ks-btn-sm" style="color:var(--red-500);">Remove</button>
                                    </form>
                                    @endcan
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p style="padding:20px;text-align:center;color:var(--gray-400);font-size:13px;">No members assigned.</p>
                @endif
            </div>
        </div>
    </div>

    {{-- Right Column: Status History --}}
    <div class="ks-card">
        <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Status History</p></div>
        <div class="ks-card-body">
            @forelse($project->statusHistory as $sh)
            <div style="border-bottom:1px solid var(--gray-100);padding:10px 0;font-size:13px;">
                <div class="ks-strong">{{ $sh->from_status ?? '—' }} → {{ $sh->to_status }}</div>
                <div style="font-size:11px;color:var(--gray-400);margin-top:2px;">{{ $sh->created_at?->diffForHumans() ?? '—' }}</div>
            </div>
            @empty
            <p style="color:var(--gray-400);font-size:13px;">No status changes yet.</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
