@extends('erp.layouts.app') @section('title', $invoice->invoice_number)
@section('content')
 <div class="ks-page-header">
 <div style="min-width:0;flex:1;">
 <h1 class="ks-page-title">Invoice {{ $invoice->invoice_number }}
  @if($invoice->duplicate_of_id)<span class="ks-badge ks-badge-orange" style="vertical-align:middle;margin-left:8px;" title="Duplicated from {{ $invoice->duplicateOf?->invoice_number ?? '#'.$invoice->duplicate_of_id }}">Duplicate</span>@endif
 </h1>
 <p class="ks-page-sub">{{ $invoice->client->business_name }} · {{ $invoice->issue_date?->format('M d, Y') }}
  @if($invoice->duplicate_of_id && $invoice->duplicateOf)
  <br>Duplicated from <a href="{{ route('erp.invoices.show', $invoice->duplicateOf) }}" style="color:var(--role-accent);">{{ $invoice->duplicateOf->invoice_number }}</a>
  @endif
 </p>
  </div>
  <div class="ks-page-actions">
  {!! erp_status_badge($invoice->status) !!}
  {!! erp_status_badge($invoice->payment_status) !!}
  <a href="{{ route('erp.invoices.pdf', $invoice) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> PDF</a>
  @can('invoice.create')<form method="POST" action="{{ route('erp.invoices.duplicate', $invoice) }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-light ks-btn-sm" title="Creates a brand-new invoice number"><i class="ti ti-copy" style="font-size:14px;"></i> Duplicate</button></form>@endcan
 @if(!$invoice->isLocked())
 @can('invoice.lock')<form method="POST" action="{{ route('erp.invoices.lock', $invoice) }}" onsubmit="return confirm('Lock this invoice? This is irreversible.')" style="display:inline;">@csrf<button class="ks-btn ks-btn-sm" style="background:#7c3aed;color:#fff;border-color:#7c3aed;">🔒 Lock</button></form>@endcan
 @endif
 @if($invoice->status === 'locked')
 @can('invoice.send')<form method="POST" action="{{ route('erp.invoices.send', $invoice) }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-primary ks-btn-sm">Mark Sent</button></form>@endcan
 @endif
 @if(!in_array($invoice->status, ['paid','cancelled']))
 @can('invoice.cancel')<form method="POST" action="{{ route('erp.invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel this invoice?')" style="display:inline;">@csrf<button class="ks-btn ks-btn-sm" style="color:#dc2626;">Cancel</button></form>@endcan
 @endif
 </div>
</div>

@if($invoice->isLocked())
<div class="ks-alert" style="background:#f5f3ff;border:1px solid #c4b5fd;color:#5b21b6;">
 <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;">
  <span><strong>LOCKED</strong> — This invoice is immutable. Amount, currency and exchange rate cannot be modified.</span>
  <span>Rate: 1 {{ $invoice->currency }} = {{ erp_money($invoice->exchange_rate, 'PKR') }}</span>
 </div>
</div>
@endif

{{-- Line items table --}}
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Description</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr></thead>
    <tbody>
    @foreach($invoice->items as $item)
     <tr>
      <td data-label="Description">{{ $item->description }}</td>
      <td data-label="Qty" style="text-align:center;">{{ $item->quantity }}</td>
      <td data-label="Unit Price" style="text-align:right;">{{ erp_money($item->unit_price, $invoice->currency) }}</td>
      <td data-label="Total" style="text-align:right;font-weight:600;">{{ erp_money($item->total, $invoice->currency) }}</td>
     </tr>
    @endforeach
    </tbody>
   </table>
  </div>
 </div>
</div>

{{-- Summary cards --}}
<div class="ks-grid-2" style="grid-template-columns:1fr 1fr;margin-top:20px;">
 <div class="ks-card">
  <div class="ks-card-body">
   <h3 class="ks-card-title" style="margin-bottom:12px;">Totals</h3>
   <div style="font-size:13px;">
    <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Subtotal</span><span>{{ erp_money($invoice->subtotal, $invoice->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Discount</span><span>- {{ erp_money($invoice->discount, $invoice->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Tax</span><span>+ {{ erp_money($invoice->tax, $invoice->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:1px solid var(--gray-100);margin-top:4px;font-weight:600;"><span>Total</span><span>{{ erp_money($invoice->total, $invoice->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;color:#16a34a;"><span>Paid</span><span>{{ erp_money($invoice->paid_amount, $invoice->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;font-weight:500;color:#dc2626;"><span>Balance</span><span>{{ erp_money($invoice->balance, $invoice->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;color:var(--gray-500);"><span>Due Date</span><span>{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</span></div>
   </div>
  </div>
 </div>
 <div class="ks-card">
  <div class="ks-card-body">
   <h3 class="ks-card-title" style="margin-bottom:12px;">Payment History</h3>
   <div class="space-y-2">
    @forelse($invoice->payments as $p)
    <a href="{{ route('erp.finance.payments.show', $p) }}" style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;text-decoration:none;color:inherit;">
     <span>{{ $p->payment_number }} · {{ $p->payment_date?->format('Y-m-d') }}</span>
     <span style="font-weight:600;">{{ erp_money($p->pivot->amount, $p->currency) }}</span>
    </a>
    @empty <p style="font-size:13px;color:var(--gray-400);">No payments recorded yet.</p> @endforelse
   </div>
   <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--gray-100);">
    <h4 style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-400);margin-bottom:8px;">Status History</h4>
    @forelse($invoice->statusHistory as $h)
    <div style="display:flex;justify-content:space-between;padding:4px 0;font-size:12px;color:var(--gray-500);border-bottom:1px solid var(--gray-100);gap:8px;flex-wrap:wrap;">
     <span>{{ $h->from_status ?? '—' }} → {{ $h->to_status }}</span>
     <span style="white-space:nowrap;">{{ $h->changedBy->name ?? 'System' }}</span>
    </div>
    @empty <p style="font-size:12px;color:var(--gray-400);">No history.</p> @endforelse
   </div>
  </div>
 </div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
    .ks-grid-2[style*="grid-template-columns:1fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
</style>
@endpush
