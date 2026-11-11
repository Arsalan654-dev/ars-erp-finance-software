@extends('erp.layouts.app')
@section('title', 'Approval: Invoice #' . ($invoice->invoice_number ?? '—'))
@section('content')

<div class="ks-page-header">
    <div>
        <a href="{{ route('erp.finance-approvals.index') }}" style="font-size:12px;color:var(--gray-400);text-decoration:none;">← Back to Approvals</a>
        <h1 class="ks-page-title">Invoice Approval</h1>
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            {!! erp_status_badge($invoice->latestApproval?->status ?? 'pending') !!}
            <span style="font-size:13px;color:var(--gray-400);">{{ $invoice->invoice_number ?? '—' }}</span>
        </div>
    </div>
</div>

<div class="ks-grid-3" style="grid-template-columns:2fr 1fr;gap:22px;">
    {{-- Invoice Details --}}
    <div class="ks-card">
        <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Invoice Details</p></div>
        <div class="ks-card-body">
            <div class="grid grid-cols-2 gap-3" style="font-size:13px;">
                <div><span style="color:var(--gray-400);">Invoice:</span> <span class="ks-strong">{{ $invoice->invoice_number ?? '—' }}</span></div>
                <div><span style="color:var(--gray-400);">Client:</span> {{ $invoice->client?->business_name ?? '—' }}</div>
                <div><span style="color:var(--gray-400);">Amount:</span> {{ erp_money($invoice->total ?? 0, 'PKR') }}</div>
                <div><span style="color:var(--gray-400);">Created By:</span> {{ $invoice->creator?->name ?? '—' }}</div>
                <div><span style="color:var(--gray-400);">Status:</span> {{ ucfirst($invoice->status) }}</div>
                @if($invoice->latestApproval)
                <div><span style="color:var(--gray-400);">Decision:</span> <span class="ks-strong">{{ ucfirst($invoice->latestApproval->status) }}</span></div>
                @if($invoice->latestApproval->notes)
                <div class="col-span-2"><span style="color:var(--gray-400);">Notes:</span> {{ $invoice->latestApproval->notes }}</div>
                @endif
                @endif
            </div>
        </div>
    </div>

    {{-- Approve / Reject Actions --}}
    <div class="ks-card">
        <div class="ks-card-header"><p class="ks-card-title" style="margin:0;">Actions</p></div>
        <div class="ks-card-body">
            @if(!$invoice->latestApproval || !in_array($invoice->latestApproval->status, ['approved','rejected']))
            <div class="space-y-4">
                <form method="POST" action="{{ route('erp.finance-approvals.approve', $invoice) }}" onsubmit="return confirm('Approve this invoice?')">
                    @csrf
                    <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;justify-content:center;"><i class="ti ti-check" style="font-size:14px;"></i> Approve</button>
                </form>

                <form method="POST" action="{{ route('erp.finance-approvals.reject', $invoice) }}" class="space-y-2" onsubmit="return confirm('Reject this invoice?')">
                    @csrf
                    <textarea name="notes" rows="3" class="ks-form-input" placeholder="Rejection reason..." required></textarea>
                    <button type="submit" class="ks-btn ks-btn-light" style="width:100%;justify-content:center;color:var(--red-500);"><i class="ti ti-x" style="font-size:14px;"></i> Reject</button>
                </form>
            </div>
            @else
            <div style="text-align:center;padding:20px 0;">
                <p style="font-size:13px;color:var(--gray-400);">This invoice has been {{ $invoice->latestApproval->status }}.</p>
                @if($invoice->latestApproval->decided_at)
                <p style="font-size:12px;color:var(--gray-400);margin-top:4px;">{{ $invoice->latestApproval->decided_at->format('d M Y H:i') }}</p>
                @endif
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
