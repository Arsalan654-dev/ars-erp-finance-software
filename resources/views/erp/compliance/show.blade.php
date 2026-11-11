@extends('erp.layouts.app') @section('title', $record->document_type)
@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
 <h1 class="ks-page-title">{{ $record->document_type }}</h1>
 <p class="ks-page-sub">{{ $record->client->business_name ?? 'N/A' }} · {{ $record->reference_number }}</p>
 </div>
 <div class="ks-page-actions">
 {!! erp_status_badge($record->status) !!}
 @if($record->expiry_date)
 <span class="ks-badge {{ $record->expiry_date->isPast() ? 'ks-badge-red' : 'ks-badge-gray' }}">Expires {{ $record->expiry_date->format('M d, Y') }}</span>
 @endif
 @can('compliance.update')
 <a href="{{ route('erp.compliance.edit', $record) }}" class="ks-btn ks-btn-primary ks-btn-sm">Edit</a>
 @endcan
 </div>
</div>

<div class="ks-grid-2" style="grid-template-columns:2fr 1fr;">
 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">Details</h3>
 <div style="font-size:13px;">
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Issuing Authority</span><span>{{ $record->issuing_authority ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Issue Date</span><span>{{ $record->issue_date?->format('Y-m-d') ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Expiry Date</span><span style="font-weight:600;">{{ $record->expiry_date?->format('Y-m-d') ?? '—' }}</span></div>
 </div>
 @if($record->notes)
 <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--gray-100);">
  <p style="font-size:13px;color:var(--gray-600);">{{ $record->notes }}</p>
 </div>
 @endif

 {{-- Renewal --}}
 <div style="margin-top:20px;padding-top:20px;border-top:1px solid var(--gray-100);">
  <h4 class="ks-card-title">One-Click Renewal</h4>
   @can('compliance.renew')
   <form method="POST" action="{{ route('erp.compliance.renew', $record) }}" class="space-y-3">
    @csrf
    <div class="ks-filter-bar">
     <input name="amount" type="number" step="0.01" placeholder="Renewal amount" class="ks-form-input" style="flex:1;min-width:0;">
     <select name="currency" class="ks-form-input" style="max-width:100px;">@foreach(['USD','GBP','EUR','PKR'] as $cur)<option value="{{ $cur }}">{{ $cur }}</option>@endforeach</select>
    </div>
    <button type="submit" class="ks-btn" style="width:100%;background:#16a34a;color:#fff;border-color:#16a34a;">Mark Renewed + Create Invoice</button>
   </form>
   @endcan
 </div>
 </div></div>

 <div class="space-y-6">
  {{-- Documents --}}
  <div class="ks-card"><div class="ks-card-body">
  <h3 class="ks-card-title">Attached Documents</h3>
  <div class="space-y-2">
   @forelse($record->documents as $doc)
   <div style="display:flex;align-items:center;justify-content:space-between;padding:12px;border-radius:10px;border:1px solid var(--gray-100);background:var(--gray-50);">
    <div>
     <p style="font-size:13px;font-weight:600;">{{ $doc->original_filename }}</p>
     <p style="font-size:11px;color:var(--gray-400);">{{ round($doc->size / 1024, 1) }} KB · {{ $doc->created_at->format('Y-m-d') }}</p>
    </div>
    <a href="{{ route('erp.documents.show', $doc) }}" class="role-text ks-btn ks-btn-xs">View</a>
   </div>
   @empty <p style="font-size:13px;color:var(--gray-400);text-align:center;padding:24px 0;">No documents attached.</p> @endforelse
  </div>
  </div></div>

  {{-- Renewal events --}}
  <div class="ks-card"><div class="ks-card-body">
  <h3 class="ks-card-title">Renewal / Reminder Events</h3>
  <div class="space-y-2">
   @forelse($record->renewalEvents as $event)
   <div style="display:flex;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;gap:8px;flex-wrap:wrap;">
    <span style="font-weight:600;">{{ str_replace('_', ' ', ucwords($event->event_type)) }}</span>
    <span style="font-size:11px;color:var(--gray-400);white-space:nowrap;">{{ $event->triggered_at }}</span>
   </div>
   @empty <p style="font-size:13px;color:var(--gray-400);text-align:center;padding:16px 0;">No events yet.</p> @endforelse
  </div>
  </div></div>
 </div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
 .ks-grid-2[style*="grid-template-columns:2fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>
@endpush
