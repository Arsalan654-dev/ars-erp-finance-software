@extends('erp.layouts.app') @section('title', $quotation->quotation_number)
@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
 <h1 class="ks-page-title">Quotation {{ $quotation->quotation_number }}
  @if($quotation->duplicate_of_id)<span class="ks-badge ks-badge-orange" style="vertical-align:middle;margin-left:8px;" title="Duplicated from {{ $quotation->duplicateOf?->quotation_number ?? '#'.$quotation->duplicate_of_id }}">Duplicate</span>@endif
 </h1>
 <p class="ks-page-sub">{{ $quotation->client->business_name }} · {{ $quotation->created_at->format('M d, Y') }}</p>
 @if($quotation->duplicate_of_id && $quotation->duplicateOf)
 <p class="ks-page-sub" style="margin-top:2px;">Duplicated from <a href="{{ route('erp.quotations.show', $quotation->duplicateOf) }}" style="color:var(--role-accent);">{{ $quotation->duplicateOf->quotation_number }}</a></p>
 @endif
 </div>
 <div class="ks-page-actions">
  {!! erp_status_badge($quotation->status) !!}
  <a href="{{ route('erp.quotations.pdf', $quotation) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-file-download" style="font-size:14px;"></i> PDF</a>
  @can('quotation.create')
  <form method="POST" action="{{ route('erp.quotations.duplicate', $quotation) }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-light ks-btn-sm" title="Creates a brand-new quotation number"><i class="ti ti-copy" style="font-size:14px;"></i> Duplicate</button></form>
  @endcan
  @if($quotation->status === 'draft')
  @can('quotation.send')<form method="POST" action="{{ route('erp.quotations.send', $quotation) }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-primary ks-btn-sm">Mark Sent</button></form>@endcan
  @endif
  @if($quotation->status === 'sent')
  @can('quotation.update')<form method="POST" action="{{ route('erp.quotations.accept', $quotation) }}" style="display:inline;">@csrf<button class="ks-btn" style="background:#16a34a;color:#fff;border-color:#16a34a;">Mark Accepted</button></form>@endcan
  @endif
  @if($quotation->status === 'accepted')
  @can('quotation.convert')<form method="POST" action="{{ route('erp.quotations.convert', $quotation) }}" style="display:inline;">@csrf<button class="ks-btn ks-btn-primary ks-btn-sm">Convert to Invoice</button></form>@endcan
  @endif
  @if(in_array($quotation->status, ['draft','rejected','expired']))
  @can('quotation.delete')<form method="POST" action="{{ route('erp.quotations.destroy', $quotation) }}" onsubmit="return confirm('Delete quotation?')" style="display:inline;">@csrf @method('DELETE')<button class="ks-btn ks-btn-sm" style="color:#dc2626;">Delete</button></form>@endcan
  @endif
 </div>
</div>

{{-- Line items table --}}
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Description</th><th style="text-align:center;">Qty</th><th style="text-align:right;">Unit Price</th><th style="text-align:right;">Total</th></tr></thead>
    <tbody>
    @foreach($quotation->items as $item)
     <tr>
      <td data-label="Description">{{ $item->description }}</td>
      <td data-label="Qty" style="text-align:center;">{{ $item->quantity }}</td>
      <td data-label="Unit Price" style="text-align:right;">{{ erp_money($item->unit_price, $quotation->currency) }}</td>
      <td data-label="Total" style="text-align:right;font-weight:600;">{{ erp_money($item->total, $quotation->currency) }}</td>
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
   <h3 class="ks-card-title" style="margin-bottom:12px;">Details</h3>
   <div style="font-size:13px;">
    <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Subtotal</span><span>{{ erp_money($quotation->subtotal, $quotation->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Discount</span><span>- {{ erp_money($quotation->discount, $quotation->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Tax</span><span>+ {{ erp_money($quotation->tax, $quotation->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:8px 0;border-top:1px solid var(--gray-100);margin-top:4px;font-weight:600;"><span>Total</span><span>{{ erp_money($quotation->total, $quotation->currency) }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;color:var(--gray-500);"><span>Valid Until</span><span>{{ $quotation->valid_until?->format('Y-m-d') ?? '—' }}</span></div>
    @if($quotation->exchange_rate)
    <div style="display:flex;justify-content:space-between;padding:6px 0;color:var(--gray-500);"><span>Exchange Rate</span><span>{{ number_format((float) $quotation->exchange_rate, 4) }} PKR/{{ $quotation->currency }}</span></div>
    <div style="display:flex;justify-content:space-between;padding:6px 0;color:var(--gray-500);"><span>≈ PKR Equivalent</span><span>{{ erp_money($quotation->total * (float) $quotation->exchange_rate, 'PKR') }}</span></div>
    @endif
   </div>
   @if($quotation->terms)
   <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--gray-100);">
    <p style="font-size:11px;font-weight:700;text-transform:uppercase;color:var(--gray-400);margin-bottom:4px;">Terms</p>
    <p style="font-size:13px;color:var(--gray-600);">{{ $quotation->terms }}</p>
   </div>
   @endif
  </div>
 </div>
 <div class="ks-card">
  <div class="ks-card-body">
   <h3 class="ks-card-title" style="margin-bottom:12px;">Status History</h3>
   <div class="space-y-2">
    @forelse($quotation->statusHistory as $h)
    <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;gap:8px;flex-wrap:wrap;">
     <span>{{ $h->from_status ?? '—' }} → <span style="font-weight:600;">{{ $h->to_status }}</span></span>
     <span style="font-size:11px;color:var(--gray-400);white-space:nowrap;">{{ $h->changedBy->name ?? 'System' }} · {{ $h->created_at }}</span>
    </div>
    @empty <p style="font-size:13px;color:var(--gray-400);">No history.</p> @endforelse
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
