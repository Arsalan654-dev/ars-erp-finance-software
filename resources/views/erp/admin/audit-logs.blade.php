@extends('erp.layouts.app')
@section('title', 'Audit Logs')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Audit Logs</h1></div>
</div>

<div class="ks-card">
 <div class="ks-card-body" style="padding:16px 20px;">
  <form method="GET" class="ks-filter-bar">
   <input type="text" name="action" value="{{ request('action') }}" placeholder="Action (e.g. DOCUMENT_DOWNLOAD)" class="ks-form-input ks-filter-search">
   <input type="text" name="entity_type" value="{{ request('entity_type') }}" placeholder="Entity (e.g. invoice)" class="ks-form-input ks-filter-search">
   <select name="user_id" class="ks-form-input" style="max-width:180px;"><option value="">All Users</option>@foreach(\App\Models\User::all() as $u)<option value="{{ $u->id }}" @selected(request('user_id')==$u->id)>{{ $u->name }}</option>@endforeach</select>
   <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Filter</button>
  </form>
 </div>
</div>

<div class="ks-card">
 <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Audit Trail <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $logs->total() }}</span></p></div>
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>User</th><th>Action</th><th>Entity</th><th>IP</th><th>Timestamp</th></tr></thead>
    <tbody>
    @forelse($logs as $l)
     <tr>
      <td data-label="User">{{ $l->user->name ?? 'System' }}</td>
      <td data-label="Action" style="font-family:monospace;font-size:12px;">{{ $l->action }}</td>
      <td data-label="Entity" style="color:var(--gray-500);">{{ $l->entity_type }}@if($l->entity_id) #{{ $l->entity_id }}@endif</td>
      <td data-label="IP" style="color:var(--gray-500);">{{ $l->ip_address }}</td>
      <td data-label="Timestamp" style="color:var(--gray-500);">{{ $l->created_at }}</td>
     </tr>
    @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No audit logs.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $logs->links() }}</div>
</div>
@endsection
