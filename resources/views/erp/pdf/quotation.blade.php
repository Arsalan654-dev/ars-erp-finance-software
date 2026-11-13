<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Quotation {{ $quotation->quotation_number }}</title>
<style>
 body { font-family: 'DejaVu Sans', sans-serif; color: #1f2937; font-size: 13px; }
 .header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 3px solid #10B981; padding-bottom: 16px; margin-bottom: 20px; }
 .brand { font-size: 20px; font-weight: bold; color: #0B1F3A; }
 .meta { text-align: right; font-size: 12px; }
 h2 { color: #374151; margin: 0 0 4px 0; }
 .info-box { display: flex; justify-content: space-between; margin-bottom: 20px; font-size: 12px; }
 table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
 th { background: #FEE3D6; color: #0B1F3A; text-align: left; padding: 8px; font-size: 11px; text-transform: uppercase; }
 td { padding: 8px; border-bottom: 1px solid #e5e7eb; }
 .right { text-align: right; }
 .totals { width: 40%; margin-left: auto; font-size: 12px; }
 .totals tr td { border-bottom: none; padding: 4px 8px; }
 .totals .grand { font-weight: bold; font-size: 15px; border-top: 2px solid #10B981; }
 .footer { margin-top: 40px; font-size: 10px; color: #9ca3af; border-top: 1px solid #e5e7eb; padding-top: 10px; text-align: center; }
</style>
</head>
<body>
<div class="header">
 <div>
 <div class="brand">ARS ERP</div>
 <div style="font-size:11px; color:#6b7280;">Tax &amp; Compliance Services</div>
 </div>
 <div class="meta">
 <h2>QUOTATION</h2>
 <div>{{ $quotation->quotation_number }}</div>
 <div>{{ $quotation->created_at->format('d M Y') }}</div>
 </div>
</div>

<div class="info-box">
 <div>
 <strong>Prepared For</strong><br>
 {{ $quotation->client->business_name }}<br>
 @if($quotation->client->contact_name){{ $quotation->client->contact_name }}<br>@endif
 {{ $quotation->client->email ?? '' }}
 </div>
 <div>
 <strong>Prepared By</strong><br>
 {{ $quotation->creator->name ?? 'ARS ERP' }}<br>
 ARS ERP Finance Software
 </div>
</div>

<table>
 <thead><tr><th>Description</th><th class="right">Qty</th><th class="right">Unit Price</th><th class="right">Total</th></tr></thead>
 <tbody>
 @foreach($quotation->items as $item)
 <tr>
 <td>{{ $item->description }}</td>
 <td class="right">{{ $item->quantity }}</td>
 <td class="right">{{ number_format($item->unit_price, 2) }} {{ $quotation->currency }}</td>
 <td class="right">{{ number_format($item->total, 2) }} {{ $quotation->currency }}</td>
 </tr>
 @endforeach
 </tbody>
</table>

<div class="totals">
 <table>
 <tr><td>Subtotal</td><td class="right">{{ number_format($quotation->subtotal, 2) }} {{ $quotation->currency }}</td></tr>
 @if($quotation->discount > 0)
 <tr><td>Discount</td><td class="right">- {{ number_format($quotation->discount, 2) }} {{ $quotation->currency }}</td></tr>
 @endif
 <tr><td>Tax</td><td class="right">{{ number_format($quotation->tax, 2) }} {{ $quotation->currency }}</td></tr>
 <tr class="grand"><td>Total</td><td class="right">{{ number_format($quotation->total, 2) }} {{ $quotation->currency }}</td></tr>
 </table>
</div>

@if($quotation->notes)
<div style="font-size:11px; color:#6b7280; margin-top:10px;">
 <strong>Notes:</strong> {{ $quotation->notes }}
</div>
@endif

<div class="footer">
 Valid until {{ $quotation->valid_until ? $quotation->valid_until->format('d M Y') : 'N/A' }} · ARS ERP Finance Software Services<br>
 This quotation is subject to our standard terms &amp; conditions.
</div>
</body>
</html>