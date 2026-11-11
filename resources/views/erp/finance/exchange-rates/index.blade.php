@extends('erp.layouts.app')
@section('title', 'Exchange Rates')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Exchange Rate Management</h1></div>
 <div class="ks-page-actions">
  <span class="ks-badge ks-badge-gray">Default rates: USD {{ $defaults['USD'] ?? '?' }} · GBP {{ $defaults['GBP'] ?? '?' }} · EUR {{ $defaults['EUR'] ?? '?' }}</span>
 </div>
</div>

<div class="ks-stat-grid" style="margin-bottom:22px;">
 @foreach(['USD','GBP','EUR'] as $cur)
 <div class="ks-card">
  <div class="ks-card-body">
   <h3 class="ks-card-title">Add {{ $cur }} → PKR Rate</h3>
   <form method="POST" action="{{ route('erp.finance.exchange-rates.store') }}" class="space-y-3">
    @csrf
    <input type="hidden" name="from_currency" value="{{ $cur }}">
    <input type="number" name="rate" step="0.0001" placeholder="Rate" required class="ks-form-input">
    <input type="date" name="date" value="{{ now()->toDateString() }}" class="ks-form-input">
    <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;">Save Rate</button>
   </form>
  </div>
 </div>
 @endforeach
</div>

<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>From</th><th>To</th><th style="text-align:right;">Rate</th><th>Date</th><th>Set By</th><th style="text-align:center;">Action</th></tr></thead>
    <tbody>
    @forelse($rates as $r)
     <tr>
      <td data-label="From" style="font-weight:600;">{{ $r->from_currency }}</td>
      <td data-label="To">{{ $r->to_currency }}</td>
      <td data-label="Rate" style="text-align:right;font-weight:600;">{{ number_format($r->rate, 4) }}</td>
      <td data-label="Date" style="color:var(--gray-500);">{{ $r->date?->format('Y-m-d') }}</td>
      <td data-label="Set By" style="color:var(--gray-500);">{{ $r->setter->name ?? '—' }}</td>
      <td data-label="Action" style="text-align:center;">
       <form method="POST" action="{{ route('erp.finance.exchange-rates.destroy', $r) }}" onsubmit="return confirm('Remove this rate?')" style="display:inline;">@csrf @method('DELETE')<button class="ks-btn ks-btn-xs" style="color:#dc2626;">Delete</button></form>
      </td>
     </tr>
    @empty <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">No exchange rates stored yet.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $rates->links() }}</div>
</div>
@endsection
