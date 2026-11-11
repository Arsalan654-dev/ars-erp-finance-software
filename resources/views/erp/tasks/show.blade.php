@extends('erp.layouts.app') @section('title', $task->title)
@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
 <h1 class="ks-page-title">{{ $task->title }}</h1>
 <p class="ks-page-sub">{{ $task->task_number }} · {{ $task->client->business_name ?? 'No client' }} · {{ $task->department }}</p>
 </div>
 <div class="ks-page-actions">
 {!! erp_status_badge($task->status) !!} {!! erp_status_badge($task->priority) !!}
 @if($task->isOverdue())<span class="ks-badge ks-badge-red">Overdue</span>@endif
 </div>
</div>

<div class="ks-grid-2" style="grid-template-columns:2fr 1fr;">
 <div class="space-y-6">
  {{-- Details --}}
  <div class="ks-card">
   <div class="ks-card-body">
    <h3 class="ks-form-label" style="margin-bottom:12px;text-transform:uppercase;letter-spacing:0.06em;">Task Details</h3>
    <p style="font-size:13px;color:var(--gray-600);margin-bottom:16px;">{{ $task->description ?? 'No description.' }}</p>
    <div class="ks-form-row">
     <div><p class="ks-form-label" style="margin-bottom:2px;">Start Date</p><p style="font-size:13px;font-weight:600;">{{ $task->start_date?->format('Y-m-d') ?? '—' }}</p></div>
     <div><p class="ks-form-label" style="margin-bottom:2px;">Due Date</p><p style="font-size:13px;font-weight:600;{{ $task->isOverdue() ? 'color:#dc2626;' : '' }}">{{ $task->due_date?->format('Y-m-d') ?? '—' }}</p></div>
    </div>
    <div class="ks-form-row">
     <div><p class="ks-form-label" style="margin-bottom:2px;">Created By</p><p style="font-size:13px;font-weight:600;">{{ $task->creator->name ?? '—' }}</p></div>
     <div><p class="ks-form-label" style="margin-bottom:2px;">Completed At</p><p style="font-size:13px;font-weight:600;">{{ $task->completed_at?->format('Y-m-d H:i') ?? '—' }}</p></div>
    </div>
    <div style="display:flex;flex-wrap:wrap;gap:6px;margin-top:16px;">
     @can('task.update')
     @foreach(['pending','assigned','in_progress','review','completed'] as $s)
     @if($task->status !== $s)
     <form method="POST" action="{{ route('erp.tasks.status', $task) }}">
      @csrf
      <input type="hidden" name="status" value="{{ $s }}">
      <button class="ks-btn ks-btn-xs ks-btn-light">{{ $s === 'in_progress' ? 'Start' : ucwords(str_replace('_',' ',$s)) }}</button>
     </form>
     @endif
     @endforeach
     @endcan
    </div>
   </div>
  </div>

  {{-- Comments --}}
  <div class="ks-card">
   <div class="ks-card-header"><h3 class="ks-card-title">Comments</h3></div>
   <div class="ks-card-body">
    @can('task.update')
     <form method="POST" action="{{ route('erp.tasks.comments', $task) }}" class="ks-filter-bar" style="margin-bottom:16px;">
      @csrf
      <input name="content" placeholder="Add a comment..." required class="ks-form-input ks-filter-search">
      <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Comment</button>
     </form>
     @endcan
    <div class="space-y-3">
     @forelse($task->comments->sortByDesc('created_at') as $c)
     <div style="padding:12px;border-radius:10px;background:var(--gray-50);border:1px solid var(--gray-100);">
      <p style="font-size:13px;color:var(--gray-700);">{{ $c->content }}</p>
      <p style="font-size:11px;color:var(--gray-400);margin-top:6px;">{{ $c->user->name ?? 'Unknown' }} · {{ $c->created_at->diffForHumans() }}</p>
     </div>
     @empty <p style="text-align:center;color:var(--gray-400);font-size:13px;padding:16px 0;">No comments.</p> @endforelse
    </div>
   </div>
  </div>
 </div>

 <div class="space-y-6">
  {{-- Assignees --}}
  <div class="ks-card">
   <div class="ks-card-header"><h3 class="ks-card-title">Assignees</h3></div>
   <div class="ks-card-body">
    <div class="space-y-2 mb-4">
     @forelse($task->assignees as $a)
     <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
      <span>{{ $a->name }}</span>
      <span style="font-size:11px;color:var(--gray-400);">{{ $a->pivot->assigned_at ? \Carbon\Carbon::parse($a->pivot->assigned_at)->format('M d') : '' }}</span>
     </div>
     @empty <p style="font-size:13px;color:var(--gray-400);">Unassigned.</p> @endforelse
    </div>
    @can('task.assign')
     <form method="POST" action="{{ route('erp.tasks.assign', $task) }}" class="ks-filter-bar">
      @csrf
      <select name="assignee_id" required class="ks-form-input" style="flex:1;min-width:0;">
       <option value="">Select user...</option>
       @foreach($assignees as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach
      </select>
      <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Assign</button>
     </form>
     @endcan
   </div>
  </div>

  {{-- Escalation --}}
  <div class="ks-card">
   <div class="ks-card-header"><h3 class="ks-card-title">Escalations</h3></div>
   <div class="ks-card-body">
    <div class="space-y-2 mb-4">
     @forelse($task->escalations as $e)
     <div style="display:flex;justify-content:space-between;font-size:13px;">
      <span style="font-weight:600;">Level {{ $e->escalation_level }}</span>
      <span style="font-size:11px;color:var(--gray-400);">{{ $e->escalated_at?->format('M d') }}</span>
     </div>
     @empty <p style="font-size:13px;color:var(--gray-400);">No escalations.</p> @endforelse
    </div>
    @can('task.escalate')
     <form method="POST" action="{{ route('erp.tasks.escalate', $task) }}" class="ks-filter-bar">
      @csrf
      <input name="reason" placeholder="Reason (optional)" class="ks-form-input ks-filter-search">
      <button type="submit" class="ks-btn ks-btn-sm" style="background:#ea580c;color:#fff;border-color:#ea580c;">Escalate</button>
     </form>
     @endcan
   </div>
  </div>

  {{-- Status history --}}
  <div class="ks-card">
   <div class="ks-card-header"><h3 class="ks-card-title">Status History</h3></div>
   <div class="ks-card-body">
    <div class="space-y-1" style="font-size:12px;">
     @forelse($task->statusHistory as $h)
     <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);gap:8px;flex-wrap:wrap;">
      <span>{{ $h->from_status ?? '—' }} → <span style="font-weight:600;">{{ $h->to_status }}</span></span>
      <span style="color:var(--gray-400);white-space:nowrap;">{{ $h->changedBy->name ?? 'System' }}</span>
     </div>
     @empty <p style="font-size:13px;color:var(--gray-400);">No history.</p> @endforelse
    </div>
   </div>
  </div>
 </div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
    .ks-grid-2[style*="grid-template-columns:2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush
