@extends('erp.layouts.app') @section('title', 'Record Payment')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Record Payment</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.finance.payments.store') }}" enctype="multipart/form-data" class="space-y-4">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Client *</label><select name="client_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->business_name }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Currency</label><select name="currency" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach(['PKR','USD','GBP','EUR'] as $cur)<option value="{{ $cur }}">{{ $cur }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label><input type="number" step="0.01" name="amount" value="{{ old('amount') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Amount Visible on Screenshot *</label><input type="number" step="0.01" name="screenshot_amount" value="{{ old('screenshot_amount') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm" placeholder="Type the amount you see on the screenshot">
  <p class="text-xs text-gray-400 mt-1">If this differs from the Amount, the payment is flagged as a mismatch and reported to the admin.</p></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Payment Screenshot *</label><input type="file" name="screenshot" accept=".jpg,.jpeg,.png,.pdf" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Payment Date</label><input type="date" name="payment_date" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Payment Method</label><select name="payment_method" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="bank_transfer">Bank Transfer</option><option value="cash">Cash</option><option value="cheque">Cheque</option><option value="online">Online</option></select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label><input type="text" name="reference_number" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Invoice (optional)</label><select name="invoice_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="">— No allocation —</option></select></div>
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>
 </div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Record Payment</button>
 <a href="{{ route('erp.finance.payments') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection