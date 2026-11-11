@extends('erp.layouts.app') @section('title', 'Leave Management')
@section('content')
<div class="ks-page-header">
 <div>
 <h1 class="ks-page-title">Leave Management</h1>
 </div>
</div>

<div class="ks-grid-2" style="grid-template-columns:1fr 2fr;margin-bottom:22px;">
 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">New Leave Request</h3>
 @can('employee.create')
 <form method="POST" action="{{ route('erp.hr.leave.store') }}" class="space-y-3">
  @csrf
  <select name="employee_id" required class="ks-form-input">
   <option value="">Select Employee</option>
   @foreach($employees as $emp)
   <option value="{{ $emp->id }}">{{ $emp->first_name }} {{ $emp->last_name }}</option>
   @endforeach
  </select>
  <select name="type" required class="ks-form-input">
   <option value="annual">Annual</option>
   <option value="sick">Sick</option>
   <option value="personal">Personal</option>
   <option value="unpaid">Unpaid</option>
   <option value="other">Other</option>
  </select>
  <div class="ks-form-row">
   <input type="date" name="start_date" required class="ks-form-input">
   <input type="date" name="end_date" required class="ks-form-input">
  </div>
  <textarea name="reason" rows="2" placeholder="Reason..." class="ks-form-input"></textarea>
  <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;">Submit Request</button>
 </form>
 @endcan
 </div></div>

 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">Annual Leave Balances</h3>
 <div class="ks-table-wrap">
  <table class="ks-table ks-table-responsive">
   <thead><tr><th>Employee</th><th style="text-align:center;">Used</th><th style="text-align:center;">Remaining</th></tr></thead>
   <tbody>
   @foreach($employees as $emp)
   @php $b = $balances[$emp->id] ?? ['used'=>0,'remaining'=>22]; @endphp
   <tr>
    <td data-label="Employee"><span style="font-weight:600;">{{ $emp->first_name }} {{ $emp->last_name }}</span></td>
    <td data-label="Used" style="text-align:center;">{{ $b['used'] }} days</td>
    <td data-label="Remaining" style="text-align:center;font-weight:600;{{ $b['remaining'] > 5 ? 'color:#16a34a;' : 'color:#dc2626;' }}">{{ $b['remaining'] }} days</td>
   </tr>
   @endforeach
   </tbody>
  </table>
 </div>
 </div></div>
</div>

<div class="ks-card">
 <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Leave Requests <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $leaves->total() }}</span></p></div>
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Employee</th><th>Type</th><th>From</th><th>To</th><th style="text-align:center;">Days</th><th style="text-align:center;">Status</th><th style="text-align:center;">Action</th></tr></thead>
    <tbody>
    @forelse($leaves as $l)
     <tr>
      <td data-label="Employee"><span style="font-weight:600;">{{ $l->employee->first_name }} {{ $l->employee->last_name }}</span></td>
      <td data-label="Type">{{ ucfirst($l->type) }}</td>
      <td data-label="From">{{ $l->start_date->format('d M') }}</td>
      <td data-label="To">{{ $l->end_date->format('d M') }}</td>
      <td data-label="Days" style="text-align:center;font-weight:600;">{{ $l->total_days }}d</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($l->status) !!}</td>
      <td data-label="Action" style="text-align:center;">
        @if($l->status === 'pending')
        @can('attendance.manage')
        <form method="POST" action="{{ route('erp.hr.leave.decide', $l) }}" style="display:inline-flex;gap:4px;">
         @csrf
         <button name="action" value="approve" class="ks-btn ks-btn-xs" style="background:#16a34a;color:#fff;border-color:#16a34a;">Approve</button>
         <button name="action" value="reject" class="ks-btn ks-btn-xs" style="color:#dc2626;">Reject</button>
        </form>
        @endcan
       @else
       <span style="font-size:11px;color:var(--gray-400);">{{ $l->approver->name ?? '—' }}</span>
       @endif
      </td>
     </tr>
    @empty <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No leave requests yet.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $leaves->links() }}</div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
 .ks-grid-2[style*="grid-template-columns:1fr 2fr"] { grid-template-columns: 1fr !important; }
}
</style>
@endpush
