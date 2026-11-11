@extends('erp.layouts.app')
@section('title', 'SOP Library')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">SOP Library</h1></div>
</div>
<div class="ks-card">
 <div class="ks-card-body">
  <h3 class="ks-card-title">New SOP</h3>
   @can('sop.manage')
   <form method="POST" action="{{ route('erp.hr.sops.store') }}" style="display:flex;flex-direction:column;gap:12px;">
    @csrf
    <div class="ks-form-row">
     <input type="text" name="title" placeholder="SOP title" required class="ks-form-input">
     <input type="text" name="category" placeholder="Category" class="ks-form-input">
     <input type="text" name="version" placeholder="Version (e.g. 1.0)" value="1.0" class="ks-form-input">
     <input type="text" name="department" placeholder="Department" class="ks-form-input">
    </div>
    <textarea name="content" placeholder="SOP content..." rows="4" class="ks-form-input"></textarea>
    <div><button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Create SOP</button></div>
   </form>
   @endcan
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Title</th><th>Category</th><th>Version</th><th>Department</th><th style="text-align:center;">Status</th><th>Updated</th></tr></thead>
    <tbody>
    @forelse($sops as $s)
     <tr>
      <td data-label="Title" style="font-weight:600;">{{ $s->title }}</td>
      <td data-label="Category">{{ $s->category }}</td>
      <td data-label="Version">{{ $s->version }}</td>
      <td data-label="Department">{{ $s->department }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($s->status) !!}</td>
      <td data-label="Updated" style="color:var(--gray-500);">{{ $s->updated_at->format('Y-m-d') }}</td>
     </tr>
    @empty <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">No SOPs.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $sops->links() }}</div>
</div>
@endsection
