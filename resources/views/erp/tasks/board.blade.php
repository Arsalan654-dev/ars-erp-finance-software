@extends('erp.layouts.app')
@section('title', 'Task Board')

@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
  <h1 class="ks-page-title">Operations Task Board</h1>
  <p class="ks-page-sub">Tap any status card below to view & drag its cards</p>
 </div>
  <div class="ks-page-actions">
   <a href="{{ route('erp.tasks.index') }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-list" style="font-size:15px;"></i> List View</a>
   @can('task.create')<a href="{{ route('erp.tasks.create') }}" class="ks-btn ks-btn-primary ks-btn-sm"><i class="ti ti-plus" style="font-size:15px;"></i> New Task</a>@endcan
  </div>
</div>

{{-- Filters --}}
<div class="ks-card">
 <div class="ks-card-body" style="padding:16px 20px;">
  <form method="GET" class="ks-filter-bar">
   <select name="department" class="ks-form-input" style="max-width:170px;">
    <option value="">All Departments</option>
    @foreach(['US TAX','UK TAX','LEGAL'] as $d)<option value="{{ $d }}" @selected(request('department')===$d)>{{ $d }}</option>@endforeach
   </select>
   <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Filter</button>
   <span class="ks-badge ks-badge-gray" style="margin-left:auto;">
    <i class="ti ti-arrows-sort" style="font-size:12px;"></i> Drag & drop inside modal
   </span>
  </form>
 </div>
</div>

{{-- Status color helpers --}}
@php
 $statusMeta = [
  'pending'     => ['dot' => '#F59E0B', 'soft' => 'bg-amber-50 border-amber-200', 'text' => 'text-amber-700'],
  'assigned'    => ['dot' => '#3B82F6', 'soft' => 'bg-blue-50 border-blue-200', 'text' => 'text-blue-700'],
  'in_progress' => ['dot' => '#06B6D4', 'soft' => 'bg-cyan-50 border-cyan-200', 'text' => 'text-cyan-700'],
  'review'      => ['dot' => '#8B5CF6', 'soft' => 'bg-purple-50 border-purple-200', 'text' => 'text-purple-700'],
  'completed'   => ['dot' => '#10B981', 'soft' => 'bg-green-50 border-green-200', 'text' => 'text-green-700'],
  'cancelled'   => ['dot' => '#6B7280', 'soft' => 'bg-gray-100 border-gray-300', 'text' => 'text-gray-600'],
 ];
@endphp

{{-- Main content: two sections toggled responsively --}}
<div x-data="{ activeStatus: null, showBoard: false }"
     x-effect="if (showBoard && activeStatus) { $nextTick(() => { const col = document.querySelector('.kanban-column[data-status=\"' + activeStatus + '\"]'); if (col) col.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' }); }); }"
     class="space-y-6">

 {{-- Desktop: compact grid --}}
 <div class="hidden md:grid ks-stat-grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));">
  @foreach($columns as $column)
   @php $meta = $statusMeta[$column] ?? $statusMeta['pending']; @endphp
   <button type="button"
     x-on:click="activeStatus = '{{ $column }}'; showBoard = true"
     class="ks-card" style="cursor:pointer;text-align:left;padding:16px;transition:all 0.15s;border:1px solid var(--gray-200);">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
     <span style="width:10px;height:10px;border-radius:50%;background:{{ $meta['dot'] }};"></span>
     <i class="ti ti-arrow-right" style="font-size:14px;color:var(--gray-300);"></i>
    </div>
    <p style="font-size:28px;font-weight:700;color:var(--gray-900);line-height:1;">{{ $board[$column]->count() }}</p>
    <p style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--gray-500);margin-top:6px;">{{ ucwords(str_replace('_', ' ', $column)) }}</p>
    <p style="font-size:10px;color:var(--gray-400);margin-top:2px;">Click to view cards</p>
   </button>
  @endforeach
 </div>

 {{-- Mobile: row list --}}
 <div class="md:hidden ks-card">
  <div class="ks-card-body-0" style="padding:0;">
   @foreach($columns as $column)
    @php $meta = $statusMeta[$column] ?? $statusMeta['pending']; @endphp
    <button type="button"
      x-on:click="activeStatus = '{{ $column }}'; showBoard = true"
      style="display:flex;align-items:center;gap:12px;width:100%;padding:14px 16px;text-align:left;border-bottom:1px solid var(--gray-100);cursor:pointer;background:none;border-left:none;border-right:none;border-top:none;{{ $loop->last ? 'border-bottom:none;' : '' }}">
     <span style="width:8px;height:8px;border-radius:50%;background:{{ $meta['dot'] }};flex-shrink:0;"></span>
     <div style="flex:1;min-width:0;">
      <p style="font-size:13px;font-weight:600;color:var(--gray-800);">{{ ucwords(str_replace('_', ' ', $column)) }}</p>
      <p style="font-size:11px;color:var(--gray-400);">{{ $board[$column]->count() }} {{ $board[$column]->count() === 1 ? 'task' : 'tasks' }}</p>
     </div>
     <i class="ti ti-chevron-right" style="font-size:14px;color:var(--gray-300);flex-shrink:0;"></i>
    </button>
   @endforeach
  </div>
 </div>

 {{-- Modal: full kanban board --}}
 <div x-show="showBoard" x-cloak
   class="ks-modal-backdrop"
   x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100">
   <div class="absolute inset-0" x-on:click="showBoard = false" style="z-index:0;"></div>
   <div class="ks-modal-panel" style="max-width:1200px;width:95vw;max-height:90vh;display:flex;flex-direction:column;position:relative;z-index:1;">

   {{-- Header --}}
   <div class="ks-modal-header" style="flex-shrink:0;">
    <div style="display:flex;align-items:center;gap:8px;min-width:0;">
     <h3 style="font-size:16px;font-weight:700;color:var(--gray-900);">
      Task Board <span x-text="activeStatus ? activeStatus.replace(/_/g,' ').replace(/\b\w/g, c => c.toUpperCase()) : ''" class="role-text"></span>
     </h3>
    </div>
    <button x-on:click="showBoard = false" class="ks-icon-btn"><i class="ti ti-x" style="font-size:18px;"></i></button>
   </div>

   {{-- Columns --}}
   <div style="overflow:auto;padding:16px 20px;flex:1;">
    <div class="ks-kanban" style="gap:16px;">
     @foreach($columns as $column)
      @php $meta = $statusMeta[$column] ?? $statusMeta['pending']; @endphp
      <div class="kanban-column ks-kanban-col {{ $meta['soft'] }}" data-status="{{ $column }}"
        :class="activeStatus === '{{ $column }}' ? 'ks-kanban-col-active' : ''" style="min-height:140px;">
       <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;flex-shrink:0;">
        <h4 style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;{{ $meta['text'] ? 'color:var(--gray-600);' : '' }}">{{ ucwords(str_replace('_', ' ', $column)) }}</h4>
        <span class="kanban-count ks-badge ks-badge-gray" style="font-size:11px;padding:2px 8px;">{{ $board[$column]->count() }}</span>
       </div>
       <div class="kanban-list ks-kanban-list">
        @foreach($board[$column] as $task)
         <div id="task-{{ $task->id }}" data-id="{{ $task->id }}" class="kanban-card ks-kanban-card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
           <span style="font-size:11px;color:var(--gray-400);font-family:monospace;">{{ $task->task_number }}</span>
           <span style="font-size:10px;font-weight:600;text-transform:uppercase;color:var(--gray-500);">{{ $task->department }}</span>
          </div>
          <p style="font-size:13px;font-weight:500;color:var(--gray-800);margin-bottom:4px;"><a href="{{ route('erp.tasks.show', $task) }}" class="role-hover-text" style="text-decoration:none;">{{ $task->title }}</a></p>
          <div style="display:flex;align-items:center;justify-content:space-between;font-size:11px;color:var(--gray-500);">
           <span style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-right:8px;">{{ $task->client->business_name ?? '—' }}</span>
           @if($task->due_date)
            <span style="white-space:nowrap;{{ $task->isOverdue() ? 'color:#dc2626;font-weight:600;' : '' }}">{{ $task->due_date->format('d M') }}</span>
           @endif
          </div>
          <div style="margin-top:8px;display:flex;align-items:center;justify-content:space-between;">
           <span>{!! erp_status_badge($task->priority) !!}</span>
           <i class="ti ti-grip-vertical" style="font-size:14px;color:var(--gray-300);cursor:grab;"></i>
          </div>
         </div>
        @endforeach
        @if($board[$column]->isEmpty())
         <div style="font-size:12px;color:var(--gray-400);text-align:center;padding:30px 0;border:2px dashed var(--gray-200);border-radius:10px;">Drop tasks here</div>
        @endif
       </div>
      </div>
     @endforeach
    </div>
   </div>
  </div>
 </div>
</div>
@endsection

@push('styles')
<style>
 @media (max-width: 767px) {
  .ks-modal-panel { max-width: 100vw !important; width: 100vw !important; border-radius: 0 !important; max-height: 100vh !important; }
 }
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    function recomputeCounts() {
        document.querySelectorAll('.kanban-column').forEach(col => {
            const list = col.querySelector('.kanban-list');
            const badge = col.querySelector('.kanban-count');
            if (list && badge) badge.textContent = list.querySelectorAll('.kanban-card').length;
        });
    }

    document.querySelectorAll('.kanban-column').forEach(col => {
        col.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('ks-kanban-col-active');
        });
        col.addEventListener('dragleave', function() {
            this.classList.remove('ks-kanban-col-active');
        });
        col.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('ks-kanban-col-active');
            const taskId = e.dataTransfer.getData('text/plain');
            if (!taskId) return;
            const newStatus = this.dataset.status;
            const card = document.getElementById('task-' + taskId);
            if (!card) return;
            const targetList = this.querySelector('.kanban-list');
            if (targetList && card) targetList.appendChild(card);
            recomputeCounts();

            fetch('/erp/tasks/' + taskId + '/status', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ status: newStatus })
            })
            .then(r => r.json())
            .then(data => { if (!data.success) location.reload(); })
            .catch(() => location.reload());
        });
    });

    document.querySelectorAll('.kanban-card').forEach(card => {
        card.setAttribute('draggable', 'true');
        card.addEventListener('dragstart', function(e) {
            e.dataTransfer.setData('text/plain', this.dataset.id);
            this.style.opacity = '0.5';
        });
        card.addEventListener('dragend', function() {
            this.style.opacity = '1';
        });
    });
});
</script>
@endpush
