@extends('erp.layouts.app')

@section('title', 'CSR Pipeline')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">CSR Pipeline</h1>
        <p class="ks-page-sub">Leads + Clients mixed pipeline overview — overall and per-CSR breakdown.</p>
    </div>
    <div class="ks-page-actions">
        @can('lead.read')
        <a href="{{ route('erp.leads.index') }}" class="ks-btn ks-btn-light"><i class="ti ti-list" style="font-size:14px;"></i> All Leads</a>
        @endcan
    </div>
</div>

{{-- Overall Stats (8) — each card drills down into the matching list --}}
<div class="ks-stat-grid" style="grid-template-columns:repeat(4,1fr);">
    @php($cards = [
        ['total', 'Total Leads', 'blue', route('erp.leads.index')],
        ['clients', 'Clients', 'blue', route('erp.clients.index')],
        ['followUp', 'Follow-up', 'yellow', route('erp.leads.index', ['status' => 'follow_up'])],
        ['quotation', 'Quotation', 'purple', route('erp.leads.index', ['status' => 'quotation'])],
        ['convertedWithInvoice', 'Converted + Invoice', 'green', route('erp.clients.index', ['with' => 'invoice'])],
        ['lost', 'Lost', 'red', route('erp.leads.index', ['status' => 'lost'])],
        ['partiallyPaid', 'Partially Paid', 'teal', route('erp.clients.index', ['payment' => 'partial'])],
        ['fullyPaid', 'Fully Paid', 'green', route('erp.clients.index', ['payment' => 'paid'])],
    ])
    @foreach($cards as [$key, $label, $tone, $href])
    <a href="{{ $href }}" class="ks-card" style="padding:14px;text-align:center;display:block;text-decoration:none;color:inherit;">
        <div style="font-size:22px;font-weight:700;color:var(--role-accent);">{{ $counts[$key] ?? 0 }}</div>
        <div style="font-size:11px;color:var(--gray-400);text-transform:uppercase;font-weight:600;letter-spacing:0.05em;">{{ $label }}</div>
    </a>
    @endforeach
</div>

{{-- Per-CSR Breakdown --}}
<div class="ks-card" style="margin-top:22px;">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">Per-CSR Breakdown <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $rows->count() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>CSR</th>
                        <th style="text-align:center;">Total Leads</th>
                        <th style="text-align:center;">Clients</th>
                        <th style="text-align:center;">Follow-up</th>
                        <th style="text-align:center;">Quotation</th>
                        <th style="text-align:center;">Converted</th>
                        <th style="text-align:center;">Converted + Invoice</th>
                        <th style="text-align:center;">Lost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rows as $row)
                    <tr>
                        <td>
                            <span class="ks-strong">{{ $row['user']->name }}</span>
                            <div style="font-size:11px;color:var(--gray-400);">{{ $row['user']->email }}</div>
                        </td>
                        <td style="text-align:center;" class="ks-strong">{{ $row['total'] }}</td>
                        <td style="text-align:center;" class="ks-strong">{{ $row['clients'] ?? 0 }}</td>
                        <td style="text-align:center;">{{ $row['followUp'] }}</td>
                        <td style="text-align:center;">{{ $row['quotation'] }}</td>
                        <td style="text-align:center;">{{ $row['converted'] }}</td>
                        <td style="text-align:center;">{{ $row['convertedWithInvoice'] }}</td>
                        <td style="text-align:center;">{{ $row['lost'] }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center;color:var(--gray-400);padding:40px;">No CSRs found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
