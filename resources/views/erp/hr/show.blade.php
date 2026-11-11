@extends('erp.layouts.app') @section('title', $employee->first_name.' '.$employee->last_name)
@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
 <h1 class="ks-page-title">{{ $employee->first_name }} {{ $employee->last_name }}</h1>
 <p class="ks-page-sub">{{ $employee->user->email ?? '—' }} · {{ $employee->user->department ?? '—' }} · {{ $employee->employment_type }}</p>
 </div>
 <div class="ks-page-actions">
 {!! erp_status_badge($employee->status) !!}
 @can('employee.update')
 <a href="{{ route('erp.hr.edit', $employee) }}" class="ks-btn ks-btn-primary ks-btn-sm">Edit</a>
 @endcan
 </div>
</div>

<div class="ks-grid-2" style="grid-template-columns:2fr 1fr;">
 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">Personal Info</h3>
 <div style="font-size:13px;">
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Phone</span><span>{{ $employee->phone ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">DOB</span><span>{{ $employee->date_of_birth?->format('Y-m-d') ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Joined</span><span>{{ $employee->joining_date?->format('Y-m-d') ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Emergency</span><span>{{ $employee->emergency_contact ?? '—' }} {{ $employee->emergency_phone ?? '' }}</span></div>
 </div>
 @if($employee->address)
 <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--gray-100);">
  <p style="font-size:13px;color:var(--gray-600);">{{ $employee->address }}</p>
 </div>
 @endif
 </div></div>

 <div class="space-y-6">
  <div class="ks-card"><div class="ks-card-body">
  <h3 class="ks-card-title">Recent Attendance</h3>
  <div class="space-y-1" style="font-size:13px;">
   @forelse($employee->attendance->take(10) as $a)
   <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--gray-100);">
    <span>{{ $a->date->format('M d, Y') }}</span>{!! erp_status_badge($a->status) !!}
   </div>
   @empty <p style="font-size:13px;color:var(--gray-400);">No attendance records.</p> @endforelse
  </div>
  <a href="{{ route('erp.hr.attendance') }}" class="role-text" style="font-size:13px;display:inline-block;margin-top:8px;">View All →</a>
  </div></div>

  <div class="ks-card"><div class="ks-card-body">
  <h3 class="ks-card-title">Training</h3>
  <div class="space-y-1" style="font-size:13px;">
   @forelse($employee->trainingAssignments as $t)
   <div style="display:flex;justify-content:space-between;padding:4px 0;border-bottom:1px solid var(--gray-100);">
    <span>{{ $t->trainingCourse->title ?? 'N/A' }}</span>{!! erp_status_badge($t->status) !!}
   </div>
   @empty <p style="font-size:13px;color:var(--gray-400);">No training assigned.</p> @endforelse
  </div>
  </div></div>
 </div>
</div>

<div class="ks-card" style="margin-top:22px;">
 <div class="ks-card-header"><h3 class="ks-card-title">Payroll Records</h3></div>
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Period</th><th>Currency</th><th style="text-align:right;">Gross</th><th style="text-align:right;">Deductions</th><th style="text-align:right;">Net</th><th style="text-align:center;">Status</th><th style="text-align:right;">Actions</th></tr></thead>
    <tbody>
    @forelse($payrollRecords as $pr)
     <tr>
      <td data-label="Period"><span style="font-weight:600;">{{ $pr->period }}</span></td>
      <td data-label="Currency">{{ $pr->currency }}</td>
      <td data-label="Gross" style="text-align:right;">{{ erp_money($pr->gross_amount, $pr->currency) }}</td>
      <td data-label="Deductions" style="text-align:right;">{{ erp_money($pr->deductions, $pr->currency) }}</td>
      <td data-label="Net" style="text-align:right;font-weight:600;">{{ erp_money($pr->net_amount, $pr->currency) }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($pr->status) !!}</td>
      <td data-label="Actions" style="text-align:right;">
       <a href="{{ route('erp.hr.payroll.payslip', $pr) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> Payslip</a>
      </td>
     </tr>
    @empty <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No payroll records.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $payrollRecords->links() }}</div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
 .ks-grid-2[style*="grid-template-columns:2fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>
@endpush
