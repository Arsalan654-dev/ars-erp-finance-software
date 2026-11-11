<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Payslip {{ $payroll->period }}</title>
<style>
 body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 13px; }
 .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #C91F32; padding-bottom: 16px; margin-bottom: 20px; }
 .brand { font-size: 20px; font-weight: bold; color: #C91F32; }
 .meta { text-align: right; font-size: 12px; }
 h2 { color: #374151; margin: 0 0 4px 0; }
 .info-box { display: flex; justify-content: space-between; margin-bottom: 24px; font-size: 12px; }
 table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
 th { background: #FEE3D6; color: #C91F32; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; }
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
 <h2>PAYSLIP</h2>
 <div>{{ $payroll->period }}</div>
 <div>{{ $payroll->status }}</div>
 </div>
</div>

<div class="info-box">
 <div>
 <strong>Employee</strong><br>
 {{ $payroll->employee->first_name }} {{ $payroll->employee->last_name }}<br>
 {{ $payroll->employee->user->email ?? '' }}
 </div>
 <div>
 <strong>Payroll Details</strong><br>
 Currency: {{ $payroll->currency }}<br>
 Exchange Rate: {{ $payroll->exchange_rate ? '1 '.$payroll->currency.' = '.number_format($payroll->exchange_rate, 2).' PKR' : '—' }}<br>
 Payment Date: {{ $payroll->payment_date?->format('d M Y') ?? '—' }}
 </div>
</div>

<div class="amount-box">
 <div class="label">Net Pay</div>
 <div class="value">{{ number_format($payroll->net_amount, 2) }} {{ $payroll->currency }}</div>
 @if($payroll->pkr_equivalent)<div style="font-size:12px; color:#15803d;">≈ {{ number_format($payroll->pkr_equivalent, 2) }} PKR</div>@endif
</div>

<table>
 <tr><td>Gross Amount</td><td class="right">{{ number_format($payroll->gross_amount, 2) }} {{ $payroll->currency }}</td></tr>
 <tr><td>Deductions</td><td class="right">- {{ number_format($payroll->deductions ?? 0, 2) }} {{ $payroll->currency }}</td></tr>
 <tr style="font-weight:bold; border-top:2px solid #C91F32;"><td>Net Pay</td><td class="right">{{ number_format($payroll->net_amount, 2) }} {{ $payroll->currency }}</td></tr>
</table>

@if($payroll->notes)
<div style="font-size:11px; color:#6b7280; margin-top:10px;"><strong>Notes:</strong> {{ $payroll->notes }}</div>
@endif

<div class="footer">
 This is a computer-generated payslip from Consultiser ERP.
</div>
</body>
</html>
