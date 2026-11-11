@extends('erp.layouts.app')
@section('title', 'Financial Reports')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Financial Reports</h1>
        <p class="ks-page-sub">Profit & Loss, AR Aging, and Cash Flow analysis.</p>
    </div>
    <div class="ks-page-actions">
        <form method="GET" style="display:flex;flex-wrap:wrap;gap:10px;">
            <input type="date" name="from" value="{{ $from }}" class="ks-form-input" style="width:150px;">
            <input type="date" name="to" value="{{ $to }}" class="ks-form-input" style="width:150px;">
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
        </form>
    </div>
</div>

{{-- P&L Summary Cards --}}
<div class="ks-stat-grid">
    <x-stat-card label="Revenue" value="{{ erp_money($summary['revenue'], 'PKR') }}" color="green" icon="{!! $iconTrendUp !!}" />
    <x-stat-card label="Expenses" value="{{ erp_money($summary['expenses'], 'PKR') }}" color="red" icon="{!! $iconShield !!}" />
    <x-stat-card label="Net Profit / Loss" value="{{ erp_money($summary['net'], 'PKR') }}" color="{{ $summary['net'] >= 0 ? 'green' : 'red' }}" icon="{!! $iconChart !!}" />
</div>

{{-- AR Aging --}}
<div class="ks-grid-2">
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">Accounts Receivable Aging</p>
            <span class="ks-badge ks-badge-blue">{{ array_sum($agingCount) }} open invoices</span>
        </div>
        <div class="ks-card-body-0">
            <div class="ks-table-wrap">
                <table class="ks-table ks-table-responsive">
                    <thead>
                        <tr>
                            <th>Bucket</th>
                            <th style="text-align:right;">Invoices</th>
                            <th style="text-align:right;">Amount (PKR)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach([['current','Current','green'],['0_30','1–30 days','yellow'],['31_60','31–60 days','orange'],['61_90','61–90 days','red'],['90_plus','90+ days','red']] as [$key,$label,$color])
                            <tr>
                                <td><span class="ks-badge ks-badge-{{ $color }}">{{ $label }}</span></td>
                                <td style="text-align:right;">{{ $agingCount[$key] }}</td>
                                <td style="text-align:right;" class="ks-strong">{{ erp_money($aging[$key], 'PKR') }}</td>
                            </tr>
                        @endforeach
                        <tr style="background:var(--gray-50);font-weight:700;">
                            <td>Total Outstanding</td>
                            <td style="text-align:right;">{{ array_sum($agingCount) }}</td>
                            <td style="text-align:right;">{{ erp_money(array_sum($aging), 'PKR') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Cash Flow --}}
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">Cash Flow (Last 6 Months)</p>
        </div>
        <div class="ks-card-body-0">
            <div class="ks-table-wrap">
                <table class="ks-table ks-table-responsive">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th style="text-align:right;">Revenue</th>
                            <th style="text-align:right;">Expenses</th>
                            <th style="text-align:right;">Net</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($cashflow as $cf)
                            <tr>
                                <td class="ks-strong">{{ $cf['label'] }}</td>
                                <td style="text-align:right;color:#16a34a;">{{ erp_money($cf['revenue'], 'PKR') }}</td>
                                <td style="text-align:right;color:#dc2626;">{{ erp_money($cf['expenses'], 'PKR') }}</td>
                                <td style="text-align:right;color:{{ $cf['net'] >= 0 ? '#16a34a' : '#dc2626' }};" class="ks-strong">{{ erp_money($cf['net'], 'PKR') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Flagged Payments (entered amount ≠ screenshot amount) --}}
    @if(($flagged ?? collect())->isNotEmpty())
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">⚠ Flagged Payments <span class="ks-badge ks-badge-red" style="margin-left:8px;">{{ $flagged->count() }}</span></p>
        </div>
        <div class="ks-card-body-0">
            <div class="ks-table-wrap">
                <table class="ks-table ks-table-responsive">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Client</th>
                            <th style="text-align:right;">Recorded</th>
                            <th style="text-align:right;">On Screenshot</th>
                            <th>Recorded By</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($flagged as $fp)
                            <tr>
                                <td><a href="{{ route('erp.finance.payments.show', $fp) }}" class="ks-strong" style="color:var(--role-accent-strong);text-decoration:none;">{{ $fp->payment_number }}</a></td>
                                <td>{{ $fp->client->business_name ?? '—' }}</td>
                                <td style="text-align:right;">{{ erp_money($fp->amount, $fp->currency) }}</td>
                                <td style="text-align:right;color:#dc2626;font-weight:600;">{{ $fp->screenshot_amount !== null ? erp_money($fp->screenshot_amount, $fp->currency) : '—' }}</td>
                                <td>{{ $fp->recorder->name ?? '—' }}</td>
                                <td>{{ $fp->created_at->format('d M Y H:i') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>
@endsection