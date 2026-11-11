@extends('erp.layouts.app')
@section('title', 'Attendance')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Attendance — {{ \Carbon\Carbon::parse($date)->format('M d, Y') }}</h1></div>
 <div class="ks-page-actions">
  <form method="GET" class="ks-filter-bar" style="margin:0;">
   <input type="date" name="date" value="{{ $date }}" class="ks-form-input">
   <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Go</button>
  </form>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body">
  <h3 class="ks-card-title">Mark Attendance</h3>
   @can('attendance.manage')
   <form method="POST" action="{{ route('erp.hr.attendance.store') }}" class="ks-filter-bar" style="flex-wrap:wrap;">
    @csrf
    <input type="hidden" name="date" value="{{ $date }}">
    <select name="employee_id" required class="ks-form-input" style="flex:1;min-width:150px;"><option value="">Select employee...</option>@foreach(\App\Models\Employee::with('user')->get() as $e)<option value="{{ $e->id }}">{{ $e->first_name }} {{ $e->last_name }}</option>@endforeach</select>
    <select name="status" required class="ks-form-input" style="max-width:140px;">@foreach(['present','absent','late','half_day','holiday'] as $s)<option value="{{ $s }}">{{ ucwords(str_replace('_',' ',$s)) }}</option>@endforeach</select>
    <input type="time" name="clock_in" class="ks-form-input" style="max-width:130px;">
    <input type="time" name="clock_out" class="ks-form-input" style="max-width:130px;">
    <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Mark</button>
   </form>
   @endcan
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Employee</th><th>Clock In</th><th>Clock Out</th><th style="text-align:center;">Status</th><th>Notes</th></tr></thead>
    <tbody>
    @forelse($records as $r)
     <tr>
      <td data-label="Employee" style="font-weight:600;">{{ $r->employee->first_name ?? '' }} {{ $r->employee->last_name ?? '' }}</td>
      <td data-label="Clock In">{{ $r->clock_in }}</td>
      <td data-label="Clock Out">{{ $r->clock_out }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($r->status) !!}</td>
      <td data-label="Notes" style="color:var(--gray-500);">{{ $r->notes }}</td>
     </tr>
    @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No attendance records for this date.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $records->links() }}</div>
</div>
@endsection
