<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Payment Receipt {{ $payment->payment_number }}</title>
<style>
 body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 13px; }
 .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #F43F1E; padding-bottom: 16px; margin-bottom: 20px; }
 .brand { font-size: 20px; font-weight: bold; color: #C91F32; }
 .meta { text-align: right; font-size: 12px; }
 h2 { color: #374151; margin: 0 0 4px 0; }
 .info-box { display: flex; justify-content: space-between; margin-bottom: 24px; font-size: 12px; }
 table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
 th { background: #f0fdf4; color: #15803d; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; }
 td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
 .right { text-align: right; }
 .amount-box { background: #f0fdf4; border: 2px solid #16a34a; border-radius: 8px; padding: 16px; text-align: center; margin-bottom: 20px; }
 .amount-box .label { font-size: 11px; text-transform: uppercase; color: #15803d; }
 .amount-box .value { font-size: 24px; font-weight: bold; color: #166534; }
 .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; text-align: center; }
</style>
</head>
<body>
<div class="header">
 <div>
 <div class="brand">Consultiser</div>
 <div style="font-size:11px; color:#6b7280;">Tax &amp; Compliance Services</div>
 </div>
 <div class="meta">
 <h2>PAYMENT RECEIPT</h2>
 <div>{{ $payment->payment_number }}</div>
 <div>{{ $payment->payment_date?->format('d M Y') }}</div>
 </div>
</div>

<div class="info-box">
 <div>
 <strong>Received From</strong><br>
 {{ $payment->client->business_name ?? '—' }}<br>
 {{ $payment->client->email ?? '' }}
 </div>
 <div>
 <strong>Payment Details</strong><br>
 Method: {{ $payment->payment_method ?? '—' }}<br>
 Reference: {{ $payment->reference_number ?? '—' }}<br>
 Recorded By: {{ $payment->recorder->name ?? '—' }}
 </div>
</div>

<div class="amount-box">
 <div class="label">Amount Received</div>
 <div class="value">{{ number_format($payment->amount, 2) }} {{ $payment->currency }}</div>
 @if($payment->pkr_equivalent)<div style="font-size:12px; color:#15803d;">≈ {{ number_format($payment->pkr_equivalent, 2) }} PKR</div>@endif
</div>

@if($payment->invoices->isNotEmpty())
<table>
 <thead><tr><th>Invoice</th><th class="right">Amount</th></tr></thead>
 <tbody>
 @foreach($payment->invoices as $inv)
 <tr><td>{{ $inv->invoice_number }}</td><td class="right">{{ number_format($inv->pivot->amount, 2) }} {{ $inv->currency }}</td></tr>
 @endforeach
 </tbody>
</table>
@endif

<div class="footer">
 This is a computer-generated receipt from Consultiser ERP. Thank you for your business.
</div>
</body>
</html>