@extends('erp.layouts.app')
@section('title', 'Notifications')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Notifications</h1></div>
 <div class="ks-page-actions">
  <form method="POST" action="{{ route('erp.notifications.read-all') }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-light ks-btn-sm">Mark All Read</button></form>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead>
     <tr>
      <th>Notification</th>
      <th>Type</th>
      <th>Time</th>
      <th style="text-align:right;">Actions</th>
     </tr>
    </thead>
    <tbody>
     @forelse($notifications as $n)
     <tr style="{{ !$n->is_read ? 'background:var(--gray-50);' : '' }}">
      <td>
       <p style="font-size:13px;font-weight:{{ $n->is_read ? '400' : '600' }};color:{{ $n->is_read ? 'var(--gray-500)' : 'var(--gray-800)' }};margin:0;">{{ $n->title }}</p>
       @if($n->body)<p style="font-size:12px;color:var(--gray-500);margin:4px 0 0;">{{ $n->body }}</p>@endif
      </td>
      <td><span class="ks-badge ks-badge-gray" style="font-size:10px;">{{ str_replace('_',' ',$n->type) }}</span></td>
      <td><span style="font-size:12px;color:var(--gray-400);">{{ $n->created_at->diffForHumans() }}</span></td>
      <td style="text-align:right;white-space:nowrap;">
       @if($n->link)<a href="{{ $n->link }}" class="role-text" style="font-size:12px;">View</a>@endif
       @if(!$n->is_read)
       <form method="POST" action="{{ route('erp.notifications.read', $n) }}" style="display:inline;">@csrf<button style="font-size:11px;color:var(--gray-400);background:none;border:none;cursor:pointer;margin-left:8px;">Mark read</button></form>
       @endif
      </td>
     </tr>
     @empty
     <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:48px;">No notifications.</td></tr>
     @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $notifications->links() }}</div>
</div>
@endsection
