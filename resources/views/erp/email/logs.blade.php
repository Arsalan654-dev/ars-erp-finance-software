@extends('erp.layouts.app')
@section('title', 'Email Logs')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Email Logs</h1></div>
 <div class="ks-page-actions">
  <a href="{{ route('erp.email.queue') }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-mail" style="font-size:14px;"></i> Queue</a>
  <a href="{{ route('erp.email.templates') }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-template" style="font-size:14px;"></i> Templates</a>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body" style="padding:16px 20px;">
  <form method="GET" class="ks-filter-bar">
   <input type="text" name="q" value="{{ request('q') }}" placeholder="Search recipient..." class="ks-form-input ks-filter-search">
   <select name="status" class="ks-form-input" style="max-width:140px;"><option value="">All</option>@foreach(['pending','sent','failed','bounced'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach</select>
   <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Filter</button>
  </form>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Recipient</th><th>Subject</th><th>Event</th><th>Sent At</th><th style="text-align:center;">Status</th></tr></thead>
    <tbody>
    @forelse($logs as $l)
     <tr>
      <td data-label="Recipient">{{ $l->recipient_email }}</td>
      <td data-label="Subject" style="color:var(--gray-600);">{{ $l->subject }}</td>
      <td data-label="Event" style="font-family:monospace;font-size:12px;">{{ $l->event_type }}</td>
      <td data-label="Sent At" style="color:var(--gray-500);">{{ $l->sent_at?->format('Y-m-d H:i') ?? '—' }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($l->status) !!}</td>
     </tr>
    @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No email logs.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $logs->links() }}</div>
</div>
@endsection