@extends('erp.layouts.app')
@section('title', 'Bank Verification')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Bank Verification Queue</h1></div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Payment</th><th>Client</th><th>Bank</th><th>Txn Ref</th><th style="text-align:right;">Amount</th><th style="text-align:center;">Status</th><th style="text-align:right;">Actions</th></tr></thead>
    <tbody>
    @forelse($verifications as $v)
     <tr>
      <td data-label="Payment"><a href="{{ route('erp.finance.payments.show', $v->payment) }}" class="role-text" style="text-decoration:none;font-weight:600;">{{ $v->payment->payment_number }}</a></td>
      <td data-label="Client">{{ $v->payment->client->business_name ?? '—' }}</td>
      <td data-label="Bank">{{ $v->bank_name ?? '—' }}</td>
      <td data-label="Txn Ref">{{ $v->transaction_reference ?? '—' }}</td>
      <td data-label="Amount" style="text-align:right;font-weight:600;">{{ erp_money($v->payment->amount, $v->payment->currency) }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($v->status) !!}</td>
      <td data-label="Actions" style="text-align:right;">
       <a href="{{ route('erp.finance.payments.show', $v->payment) }}" class="role-text ks-btn ks-btn-xs">Review</a>
      </td>
     </tr>
    @empty <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No verification records.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $verifications->links() }}</div>
</div>
@endsection
