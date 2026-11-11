@extends('erp.layouts.app')
@section('title', 'Sales Packages')
@section('content')
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Sales Packages</h1>
        <p class="ks-page-sub">Reusable, pre-priced line items you can drop into any quotation or invoice.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.sales-packages.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:14px;"></i> New Package</a>
    </div>
</div>

<div class="ks-card" style="margin-bottom:22px;">
    <div class="ks-card-body" style="padding:14px 20px;">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <div style="flex:1;min-width:180px;">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search packages..." class="ks-form-input">
            </div>
            <select name="status" class="ks-form-input" style="width:150px;">
                <option value="">All Statuses</option>
                <option value="active" @selected(request('status')==='active')>Active</option>
                <option value="inactive" @selected(request('status')==='inactive')>Inactive</option>
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
            <a href="{{ route('erp.sales-packages.index') }}" class="ks-btn ks-btn-light">Clear</a>
        </form>
    </div>
</div>

<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">Packages <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $packages->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Package</th>
                        <th>Description</th>
                        <th style="text-align:center;">Lines</th>
                        <th style="text-align:right;">Unit Price</th>
                        <th style="text-align:center;">Currency</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($packages as $package)
                    <tr>
                        <td data-label="Package" class="ks-strong">{{ $package->name }}</td>
                        <td data-label="Description" style="color:var(--gray-500);">{{ \Illuminate\Support\Str::limit($package->description, 60) ?: '—' }}</td>
                        <td data-label="Lines" style="text-align:center;">
                            @if($package->items_count > 0)
                                <span class="ks-badge ks-badge-blue">{{ $package->items_count }} lines</span>
                            @else
                                <span style="color:var(--gray-400);">—</span>
                            @endif
                        </td>
                        <td data-label="Unit Price" style="text-align:right;font-weight:600;">{{ number_format((float) $package->unit_price, 2) }}</td>
                        <td data-label="Currency" style="text-align:center;">{{ $package->currency }}</td>
                        <td data-label="Status" style="text-align:center;">{!! $package->is_active ? erp_status_badge('active') : erp_status_badge('inactive') !!}</td>
                        <td data-label="Actions" style="text-align:center;white-space:nowrap;">
                            <form method="POST" action="{{ route('erp.sales-packages.toggle', $package) }}" style="display:inline;">@csrf
                                <button class="ks-btn ks-btn-xs ks-btn-light">{{ $package->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                            <a href="{{ route('erp.sales-packages.edit', $package) }}" class="ks-btn ks-btn-xs ks-btn-light">Edit</a>
                            <form method="POST" action="{{ route('erp.sales-packages.destroy', $package) }}" onsubmit="return confirm('Delete this package?')" style="display:inline;">@csrf @method('DELETE')
                                <button class="ks-btn ks-btn-xs" style="color:#dc2626;">Delete</button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No packages yet — create your first template.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $packages->links() }}</div>
</div>
@endsection
