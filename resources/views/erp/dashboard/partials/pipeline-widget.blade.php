@isset($pipelineCounts)
<div class="ks-card">
    <div class="ks-card-header">
        <div>
            <p class="ks-card-title" style="margin:0;">CSR Pipeline</p>
            <p class="ks-card-subtitle">Leads + Clients mixed overview</p>
        </div>
        @if(auth()->user() && auth()->user()->hasPermission('lead.read'))
        <a href="{{ route('erp.leads.pipeline') }}" class="ks-btn ks-btn-light ks-btn-sm">Open Pipeline</a>
        @endif
    </div>
    <div class="ks-card-body">
        <div class="ks-stat-grid" style="grid-template-columns:repeat(4,1fr);gap:10px;">
            @foreach([
                ['total', 'Total Leads'],
                ['clients', 'Clients'],
                ['followUp', 'Follow-up'],
                ['quotation', 'Quotation'],
                ['convertedWithInvoice', 'Converted + Invoice'],
                ['lost', 'Lost'],
                ['partiallyPaid', 'Partially Paid'],
                ['fullyPaid', 'Fully Paid'],
            ] as [$key, $label])
            <div style="padding:12px 8px;text-align:center;background:var(--gray-50);border-radius:10px;">
                <div style="font-size:20px;font-weight:700;color:var(--role-accent);">{{ $pipelineCounts[$key] ?? 0 }}</div>
                <div style="font-size:10px;color:var(--gray-400);text-transform:uppercase;font-weight:600;letter-spacing:0.04em;">{{ $label }}</div>
            </div>
            @endforeach
        </div>
        <p style="font-size:11px;color:var(--gray-400);margin:10px 0 0;">Paid / outstanding counters count distinct clients (not invoices).</p>
    </div>
</div>
@endisset
