@extends('erp.layouts.app') @section('title', $payment->payment_number)
@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
  <h1 class="ks-page-title">Payment {{ $payment->payment_number }}
   @if($payment->amount_mismatch)<span class="ks-badge ks-badge-red" style="vertical-align:middle;margin-left:8px;">Amount Mismatch</span>@endif
  </h1>
  <p class="ks-page-sub">{{ $payment->client->business_name ?? 'N/A' }} · {{ $payment->payment_date?->format('M d, Y') }}</p>
  </div>
  <div class="ks-page-actions">
  {!! erp_status_badge($payment->status) !!}
  <a href="{{ route('erp.finance.payments.receipt', $payment) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> Receipt PDF</a>
  @if(auth()->user()?->isSuperAdmin() && $payment->status !== 'verified')
  <form method="POST" action="{{ route('erp.finance.payments.destroy', $payment) }}" onsubmit="return confirm('Delete payment {{ $payment->payment_number }}? This cannot be undone.')" style="display:inline;">@csrf @method('DELETE')<button class="ks-btn ks-btn-sm" style="color:#dc2626;">Delete</button></form>
  @endif
 @if($payment->status === 'pending')
 @can('payment.verify')
 <form method="POST" action="{{ route('erp.finance.payments.acknowledge', $payment) }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-sm" style="background:#2563eb;color:#fff;border-color:#2563eb;">Acknowledge</button></form>
 <form method="POST" action="{{ route('erp.finance.payments.reject', $payment) }}" onsubmit="return confirm('Reject payment?')" style="display:inline;">@csrf<button class="ks-btn ks-btn-sm" style="color:#dc2626;">Reject</button></form>
 @endcan
 @endif
 @if($payment->status === 'acknowledged')
 @can('payment.verify')
 <form method="POST" action="{{ route('erp.finance.payments.verify', $payment) }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-sm" style="background:#16a34a;color:#fff;border-color:#16a34a;">Verify</button></form>
 <form method="POST" action="{{ route('erp.finance.payments.reject', $payment) }}" onsubmit="return confirm('Reject payment?')" style="display:inline;">@csrf<button class="ks-btn ks-btn-sm" style="color:#dc2626;">Reject</button></form>
 @endcan
 @endif
 </div>
</div>

<div class="ks-grid-2">
 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">Details</h3>
 @if($payment->amount_mismatch)
 <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#b91c1c;margin-bottom:10px;">
  <strong>⚠ Amount mismatch:</strong>  {{ erp_money($payment->amount, $payment->currency) }} was recorded, but the screenshot shows
  {{ $payment->screenshot_amount !== null ? erp_money($payment->screenshot_amount, $payment->currency) : 'a different amount' }}.
  The admin has been notified — review the screenshot before verifying.
 </div>
 @endif
 <div style="font-size:13px;">
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Amount</span><span style="font-weight:600;">{{ erp_money($payment->amount, $payment->currency) }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Amount on Screenshot</span><span @if($payment->amount_mismatch) style="color:#dc2626;font-weight:600;" @endif>{{ $payment->screenshot_amount !== null ? erp_money($payment->screenshot_amount, $payment->currency) : '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">PKR Equivalent</span><span>{{ erp_money($payment->pkr_equivalent, 'PKR') }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Exchange Rate</span><span>{{ $payment->exchange_rate ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Method</span><span>{{ $payment->payment_method ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Reference</span><span>{{ $payment->reference_number ?? '—' }}</span></div>
  @if($payment->screenshot_path)
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Screenshot</span><a href="{{ route('erp.finance.payments.screenshot', $payment) }}" class="role-text" target="_blank"><i class="ti ti-photo" style="font-size:13px;"></i> View</a></div>
  @endif
  @if($payment->acknowledged_by)
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Acknowledged By</span><span>{{ $payment->acknowledger->name ?? '—' }} <span style="color:var(--gray-400);font-size:11px;">{{ $payment->acknowledged_at?->format('d M Y H:i') }}</span></span></div>
  @endif
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Recorded By</span><span>{{ $payment->recorder->name ?? '—' }}</span></div>
  @if($payment->verified_by)
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Verified By</span><span>{{ $payment->verifier->name ?? '—' }}</span></div>
  @endif
 </div>
 </div></div>

 {{-- CSR / Recorder details (for acknowledge step) --}}
 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">Recorded By (CSR)</h3>
 @if($payment->recorder)
 <div style="font-size:13px;">
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Name</span><span class="ks-strong">{{ $payment->recorder->name }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Email</span><span>{{ $payment->recorder->email ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Phone</span><span>{{ $payment->recorder->phone ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Designation</span><span>{{ $payment->recorder->designation ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Roles</span><span>{{ $payment->recorder->roles->pluck('name')->implode(', ') ?: '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Recorded At</span><span>{{ $payment->created_at->format('d M Y H:i') }}</span></div>
 </div>
 @else
 <p style="font-size:13px;color:var(--gray-400);">No recorder info.</p>
 @endif
 @if($payment->lead)
 <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--gray-100);font-size:13px;">
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Lead</span><a href="{{ route('erp.leads.show', $payment->lead) }}" class="role-text">{{ $payment->lead->business_name ?? $payment->lead->contact_name ?? '#'.$payment->lead->id }}</a></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Conversion Type</span><span>{{ ucfirst($payment->lead->payment_type ?? '—') }}</span></div>
 </div>
 @endif
 </div></div>

 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">Allocated Invoices</h3>
 <div class="space-y-2">
  @forelse($payment->invoices as $inv)
  <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;gap:8px;flex-wrap:wrap;">
   <a href="{{ route('erp.invoices.show', $inv) }}" class="role-text">{{ $inv->invoice_number }}</a>
   <span style="font-weight:600;">{{ erp_money($inv->pivot->amount, $inv->currency) }}</span>
  </div>
  @empty <p style="font-size:13px;color:var(--gray-400);">Not allocated.</p> @endforelse
 </div>

 @if($payment->bankVerification)
 <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--gray-100);">
  <h4 style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;">Bank Verification</h4>
  <div style="font-size:13px;">
   <div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--gray-500);">Bank</span><span>{{ $payment->bankVerification->bank_name ?? '—' }}</span></div>
   <div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--gray-500);">Account</span><span>{{ $payment->bankVerification->account_number ?? '—' }}</span></div>
   <div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--gray-500);">Transaction Ref</span><span>{{ $payment->bankVerification->transaction_reference ?? '—' }}</span></div>
   <div style="display:flex;justify-content:space-between;padding:4px 0;"><span style="color:var(--gray-500);">Status</span><span>{!! erp_status_badge($payment->bankVerification->status) !!}</span></div>
  </div>
 </div>
 @elseif($payment->status === 'pending')
 <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--gray-100);">
  <h4 style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;">Add Bank Verification</h4>
  <form method="POST" action="{{ route('erp.finance.payments.bank-verification', $payment) }}" class="space-y-3">
   @csrf
   <div><label class="ks-form-label">Bank Name</label><input type="text" name="bank_name" class="ks-form-input" placeholder="e.g. HBL, Meezan, Wise" required></div>
   <div><label class="ks-form-label">Account Number</label><input type="text" name="account_number" class="ks-form-input" placeholder="Account number" required></div>
   <div><label class="ks-form-label">Transaction Reference</label><input type="text" name="transaction_reference" class="ks-form-input" placeholder="Transaction ID / Reference" required></div>
   <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm"><i class="ti ti-check" style="font-size:14px;"></i> Save Bank Details</button>
  </form>
 </div>
 @endif
 </div></div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
 .ks-grid-2 { grid-template-columns: 1fr !important; }
}
</style>
@endpush
