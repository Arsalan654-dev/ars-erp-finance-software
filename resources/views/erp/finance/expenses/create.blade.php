@extends('erp.layouts.app') @section('title', 'Record Expense')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Record Expense</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.finance.expenses.store') }}" class="space-y-4">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Category</label><input type="text" name="category" placeholder="e.g. Software, Office, Travel" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Currency</label><select name="currency" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach(['PKR','USD','GBP','EUR'] as $cur)<option value="{{ $cur }}">{{ $cur }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Amount *</label><input type="number" step="0.01" name="amount" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Expense Date</label><input type="date" name="expense_date" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>
 </div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Record Expense</button>
 <a href="{{ route('erp.finance.expenses') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection