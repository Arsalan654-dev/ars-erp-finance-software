@extends('erp.layouts.app')
@section('title', 'Document Vault')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Document Vault</h1>
        <p class="ks-page-sub">Private storage with signed URLs (10-min expiry) and full access audit trail.</p>
    </div>
    <div class="ks-page-actions">
        @can('document.upload')<a href="{{ route('erp.documents.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> Upload Document</a>@endcan
    </div>
</div>

<div class="ks-alert ks-alert-info" style="display:flex;align-items:center;gap:10px;">
    <i class="ti ti-alert-circle" style="font-size:20px;"></i>
    <div>
        <b>Private Vault:</b> Documents stored outside public web root. Downloads use short-lived signed URLs (10 min) and are fully audited.
    </div>
</div>

{{-- Filters --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <div class="ks-filter-search" style="position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search filename..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <input type="text" name="category" value="{{ request('category') }}" placeholder="Category" class="ks-form-input" style="width:180px;">
            <button type="submit" class="ks-btn ks-btn-primary">Search</button>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Documents <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $documents->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Client</th>
                        <th>Category</th>
                        <th style="text-align:right;">Size</th>
                        <th>Uploaded</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $doc)
                        <tr>
                            <td class="ks-strong">{{ $doc->original_filename }}</td>
                            <td>{{ $doc->client->business_name ?? '—' }}</td>
                            <td>{{ $doc->category ?? '—' }}</td>
                            <td style="text-align:right;">{{ round($doc->size / 1024, 1) }} KB</td>
                            <td>{{ $doc->created_at->format('Y-m-d') }}</td>
                            <td style="text-align:right;">
                                <a href="{{ route('erp.documents.show', $doc) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-eye" style="font-size:14px;"></i> View</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">No documents.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $documents->links() }}
    </div>
</div>
@endsection