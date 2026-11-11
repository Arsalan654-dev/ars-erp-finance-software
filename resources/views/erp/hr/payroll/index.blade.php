@extends('erp.layouts.app')
@section('title', 'Payroll')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Payroll Records</h1></div>
</div>
<div class="ks-card">
 <div class="ks-card-body">
  <h3 class="ks-card-title">New Payroll Record</h3>
   @can('payroll.manage')
   <form method="POST" action="{{ route('erp.hr.payroll.store') }}" style="display:flex;flex-direction:column;gap:12px;">
    @csrf
    <div class="ks-form-row" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));">
     <select name="employee_id" required class="ks-form-input"><option value="">Employee...</option>@foreach(\App\Models\Employee::with('user')->get() as $e)<option value="{{ $e->id }}">{{ $e->first_name }} {{ $e->last_name }}</option>@endforeach</select>
     <input type="month" name="period" required value="{{ now()->format('Y-m') }}" class="ks-form-input">
     <select name="currency" class="ks-form-input">@foreach(['PKR','USD','GBP','EUR'] as $cur)<option value="{{ $cur }}">{{ $cur }}</option>@endforeach</select>
     <input type="number" name="gross_amount" step="0.01" placeholder="Gross" required class="ks-form-input">
     <input type="number" name="deductions" step="0.01" placeholder="Deductions" value="0" class="ks-form-input">
     <input type="number" name="net_amount" step="0.01" placeholder="Net" required class="ks-form-input">
    </div>
    <div><button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Create Record</button></div>
   </form>
   @endcan
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Employee</th><th>Period</th><th>Currency</th><th style="text-align:right;">Gross</th><th style="text-align:right;">Net</th><th style="text-align:right;">PKR</th><th style="text-align:center;">Status</th><th style="text-align:right;">Actions</th></tr></thead>
    <tbody>
    @forelse($payrolls as $p)
     <tr>
      <td data-label="Employee" style="font-weight:600;">{{ $p->employee->first_name ?? '' }} {{ $p->employee->last_name ?? '' }}</td>
      <td data-label="Period">{{ $p->period }}</td>
      <td data-label="Currency">{{ $p->currency }}</td>
      <td data-label="Gross" style="text-align:right;">{{ erp_money($p->gross_amount, $p->currency) }}</td>
      <td data-label="Net" style="text-align:right;font-weight:600;">{{ erp_money($p->net_amount, $p->currency) }}</td>
      <td data-label="PKR" style="text-align:right;">{{ erp_money($p->pkr_equivalent, 'PKR') }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($p->status) !!}</td>
      <td data-label="Actions" style="text-align:right;">
       <a href="{{ route('erp.hr.payroll.payslip', $p) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> Payslip</a>
      </td>
     </tr>
    @empty <tr><td colspan="8" style="text-align:center;color:var(--gray-400);padding:40px;">No payroll records.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $payrolls->links() }}</div>
</div>
@endsection
