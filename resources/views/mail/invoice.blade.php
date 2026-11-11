<x-mail::message>

# {{ config('app.name') }} — Invoice

| | |
|---|---|
| **Invoice #:** | {{ $order->id }} |
| **Invoice Date:** | {{ $order->created_at ? $order->created_at->addHours(5)->format('M d, Y h:i A') : '-' }} |
| **Status:** | Paid |

## Bill To:
| | |
|---|---|
| **Name:** | {{ $order->customername }} |
| **Email:** | {{ $order->email }} |
| **Phone:** | {{ $order->phone }} |
| **Country:** | {{ $order->country?->name ?? '-' }} |
| **State:** | {{ $order->state?->name ?? '-' }} |

## Order Details
| Description | Details |
|---|---|
| **Package:** | {{ $order->product?->name ?? '-' }} HIN/HULL Report |
| **HIN/HULL Number(s):** | {{ $order->vinstable->pluck('vin')->join(', ') ?: '-' }} |
| **Quantity:** | 1 |
| **Unit Price:** | {{ $order->price }} {{ $order->currency_abbr }} |
| **Tax:** | {{ $order->tax }} {{ $order->currency_abbr }} |
| **Total Paid:** | **{{ number_format((float) $order->price + (float) $order->tax, 2) }} {{ $order->currency_abbr }}** |

<x-mail::subcopy>
Dear {{ $order->customername }},

We are pleased to confirm that your payment has been received and your order is now being processed.

Your HIN/HULL Report will be meticulously prepared and delivered to you within the next 12 hours. Please keep an eye on your email inbox, including spam or junk folders, just in case.

Thank you for choosing {{ config('app.name') }}. If you have any questions or need further assistance, please don't hesitate to contact us. We're here to help.

Regards,<br>
{{ config('app.name') }}
</x-mail::subcopy>
</x-mail::message>