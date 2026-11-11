@extends('erp.layouts.app') @section('title', $document->original_filename)
@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
 <h1 class="ks-page-title">{{ $document->original_filename }}</h1>
 <p class="ks-page-sub">{{ $document->client->business_name ?? 'No client' }} · {{ $document->category ?? 'Uncategorized' }}</p>
 </div>
  <div class="ks-page-actions">
  @can('document.download')<a href="{{ \App\Services\DocumentService::signedDownloadUrl($document) }}" class="ks-btn ks-btn-primary ks-btn-sm"><i class="ti ti-download" style="font-size:14px;"></i> Download</a>@endcan
  @can('document.delete')<form method="POST" action="{{ route('erp.documents.destroy', $document) }}" onsubmit="return confirm('Delete document?')" style="display:inline;">@csrf @method('DELETE')<button class="ks-btn ks-btn-sm" style="color:#dc2626;">Delete</button></form>@endcan
  </div>
</div>

<div class="ks-alert ks-alert-warning">
 <div style="display:flex;align-items:center;gap:8px;">
  <i class="ti ti-lock" style="font-size:16px;"></i>
  <p style="font-size:13px;">This document is stored privately. Download links expire after <strong>{{ \App\Services\DocumentService::SIGNED_URL_TTL_MINUTES }} minutes</strong> and every access is logged.</p>
 </div>
</div>

<div class="ks-grid-2" style="grid-template-columns:2fr 1fr;">
 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">File Info</h3>
 <div style="font-size:13px;">
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">MIME Type</span><span>{{ $document->mime_type ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Size</span><span>{{ round($document->size / 1024, 1) }} KB</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Uploaded By</span><span>{{ $document->uploader->name ?? '—' }}</span></div>
  <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Uploaded At</span><span>{{ $document->created_at->format('Y-m-d H:i') }}</span></div>
 </div>
 @if($document->description)
 <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--gray-100);">
  <p style="font-size:13px;color:var(--gray-600);">{{ $document->description }}</p>
 </div>
 @endif
 </div></div>

 <div class="ks-card"><div class="ks-card-body">
 <h3 class="ks-card-title">Access History (Audit Trail)</h3>
 <div class="space-y-2">
  @forelse($document->accessLogs as $log)
  <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 0;border-bottom:1px solid var(--gray-100);font-size:13px;gap:8px;flex-wrap:wrap;">
   <div><span style="font-weight:600;">{{ $log->action }}</span> by {{ $log->user->name ?? 'Unknown' }}</div>
   <span style="font-size:11px;color:var(--gray-400);white-space:nowrap;">{{ $log->created_at }} · {{ $log->ip_address }}</span>
  </div>
  @empty <p style="font-size:13px;color:var(--gray-400);text-align:center;padding:24px 0;">No access records yet.</p> @endforelse
 </div>
 </div></div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
 .ks-grid-2[style*="grid-template-columns:2fr 1fr"] { grid-template-columns: 1fr !important; }
}
</style>
@endpush
