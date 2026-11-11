@extends('erp.layouts.app')
@section('title', 'Ledger')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">General Ledger</h1></div>
</div>
<div class="ks-stat-grid" style="margin-bottom:22px;">
 <x-stat-card label="Revenue (PKR)" value="{{ erp_money($summary['revenue'], 'PKR') }}" color="green" />
 <x-stat-card label="Expenses (PKR)" value="{{ erp_money($summary['expenses'], 'PKR') }}" color="red" />
 <x-stat-card label="Net (PKR)" value="{{ erp_money($summary['net'], 'PKR') }}" color="indigo" />
</div>
<div class="ks-card">
 <div class="ks-card-body" style="padding:16px 20px;">
  <form method="GET" class="ks-filter-bar">
   <input type="date" name="from" value="{{ request('from') }}" class="ks-form-input">
   <input type="date" name="to" value="{{ request('to') }}" class="ks-form-input">
   <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Filter</button>
  </form>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Date</th><th>Account</th><th>Type</th><th>Description</th><th style="text-align:right;">Debit</th><th style="text-align:right;">Credit</th></tr></thead>
    <tbody>
    @forelse($entries as $e)
     <tr>
      <td data-label="Date" style="color:var(--gray-500);">{{ $e->entry_date?->format('Y-m-d') }}</td>
      <td data-label="Account" style="font-weight:600;">{{ $e->ledgerAccount->code ?? '—' }} {{ $e->ledgerAccount->name ?? '' }}</td>
      <td data-label="Type">{{ $e->entry_type }}</td>
      <td data-label="Description" style="color:var(--gray-500);">{{ $e->description ?? '—' }}</td>
      <td data-label="Debit" style="text-align:right;color:#dc2626;">{{ $e->entry_type === 'debit' ? erp_money($e->amount, $e->currency) : '—' }}</td>
      <td data-label="Credit" style="text-align:right;color:#16a34a;">{{ $e->entry_type === 'credit' ? erp_money($e->amount, $e->currency) : '—' }}</td>
     </tr>
    @empty <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">No ledger entries.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $entries->links() }}</div>
</div>
@endsection
