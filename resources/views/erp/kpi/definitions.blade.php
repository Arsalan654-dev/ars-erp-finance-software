@extends('erp.layouts.app')
@section('title', 'KPI Definitions')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">KPI Definitions</h1></div>
 <div class="ks-page-actions">
  <a href="{{ route('erp.kpi.index') }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-arrow-left" style="font-size:14px;"></i> Back to KPI</a>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body">
  <h3 class="ks-card-title">New KPI Definition</h3>
   @can('kpi.manage')
   <form method="POST" action="{{ route('erp.kpi.definitions.store') }}" style="display:flex;flex-direction:column;gap:12px;">
    @csrf
    <div class="ks-form-row" style="grid-template-columns:repeat(auto-fill,minmax(140px,1fr));">
     <input type="text" name="slug" placeholder="slug (auto-calc key)" required class="ks-form-input">
     <input type="text" name="name" placeholder="Name" required class="ks-form-input">
     <input type="text" name="unit" placeholder="Unit" class="ks-form-input">
     <select name="calculation_method" class="ks-form-input"><option value="auto">Auto</option><option value="manual">Manual</option></select>
     <select name="frequency" class="ks-form-input"><option value="monthly">Monthly</option><option value="weekly">Weekly</option><option value="quarterly">Quarterly</option></select>
    </div>
    <textarea name="description" placeholder="Description" rows="2" class="ks-form-input"></textarea>
    <div><button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Create</button></div>
   </form>
   @endcan
  <p style="font-size:11px;color:var(--gray-400);margin-top:10px;">Auto-calc slugs: tasks_assigned, tasks_completed, tasks_overdue, completion_rate, avg_completion_days, client_count</p>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Slug</th><th>Name</th><th>Unit</th><th>Method</th><th>Frequency</th><th style="text-align:center;">Active</th></tr></thead>
    <tbody>
    @foreach($definitions as $d)
     <tr>
      <td data-label="Slug" style="font-family:monospace;font-size:12px;">{{ $d->slug }}</td>
      <td data-label="Name" style="font-weight:600;">{{ $d->name }}</td>
      <td data-label="Unit">{{ $d->unit }}</td>
      <td data-label="Method">{{ $d->calculation_method }}</td>
      <td data-label="Frequency">{{ $d->frequency }}</td>
      <td data-label="Active" style="text-align:center;">{!! $d->is_active ? '<span style="color:#16a34a;">Yes</span>' : '<span style="color:var(--gray-400);">No</span>' !!}</td>
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $definitions->links() }}</div>
</div>
@endsection
