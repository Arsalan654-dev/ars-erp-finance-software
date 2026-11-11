@extends('erp.layouts.app')
@section('title', 'Email Queue')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Email Queue</h1></div>
 <div class="ks-page-actions">
  <form method="POST" action="{{ route('erp.email.queue.process') }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-primary ks-btn-sm"><i class="ti ti-player-play" style="font-size:14px;"></i> Process Queue</button></form>
  <a href="{{ route('erp.email.logs') }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-history" style="font-size:14px;"></i> Logs</a>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>To</th><th>Subject</th><th>Event</th><th style="text-align:center;">Retries</th><th>Scheduled</th><th style="text-align:center;">Status</th></tr></thead>
    <tbody>
    @forelse($queue as $q)
     <tr>
      <td data-label="To">{{ $q->to_email }}</td>
      <td data-label="Subject" style="color:var(--gray-600);">{{ $q->subject }}</td>
      <td data-label="Event" style="font-family:monospace;font-size:12px;">{{ $q->event_type }}</td>
      <td data-label="Retries" style="text-align:center;">{{ $q->retry_count }}/{{ $q->max_retries }}</td>
      <td data-label="Scheduled" style="color:var(--gray-500);">{{ $q->scheduled_at?->format('Y-m-d H:i') }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($q->status) !!}</td>
     </tr>
    @empty <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">Queue is empty.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $queue->links() }}</div>
</div>
@endsection
