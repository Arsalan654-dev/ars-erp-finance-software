@extends('erp.layouts.app') @section('title', 'Create Invoice')
@section('content')
<div class="reveal max-w-3xl mx-auto">
 <h1 class="ks-page-title">Create Invoice</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.invoices.store') }}" class="space-y-6">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div class="md:col-span-3">
 <label class="block text-sm font-medium text-gray-700 mb-1">Client *</label>
 <select name="client_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
 <option value="">Select client...</option>
  @foreach($clients as $c)<option value="{{ $c->id }}" @selected(request('client') == $c->id)>{{ $c->business_name }} ({{ $c->client_number }}, {{ ucfirst($c->status) }})</option>@endforeach
 </select>
 </div>
 <div>
 <label class="block text-sm font-medium text-gray-700 mb-1">Currency</label>
 <select name="currency" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
 @foreach(['USD','GBP','EUR','PKR'] as $cur)<option value="{{ $cur }}" @selected(old('currency')===$cur)>{{ $cur }}</option>@endforeach
 </select>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label><input type="date" name="issue_date" value="{{ old('issue_date', now()->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label><input type="date" name="due_date" value="{{ old('due_date', now()->addDays(30)->format('Y-m-d')) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 </div>

 <div>
 <h3 class="text-sm font-semibold text-gray-700 mb-3">Line Items</h3>
 @if(isset($packages) && $packages->isNotEmpty())
 <div class="mb-3">
  <label class="block text-xs font-medium text-gray-500 mb-1">Add from Sales Package</label>
  <select id="package-picker" onchange="addPackage(this)" class="w-full md:w-1/2 rounded-lg border border-gray-300 px-3 py-2 text-sm">
   <option value="">Choose a package…</option>
   @foreach($packages as $p)
   <option value="{{ $p->id }}" data-name="{{ $p->name }}" data-price="{{ $p->unit_price }}" data-items="{{ json_encode($p->quotationLines()) }}">{{ $p->name }} — {{ $p->currency }} {{ number_format((float) $p->unit_price, 2) }}{{ count($p->quotationLines()) > 1 ? ' ('.count($p->quotationLines()).' lines)' : '' }}</option>
   @endforeach
  </select>
 </div>
 @endif
 <div id="items" class="space-y-3">
 <div class="grid grid-cols-12 gap-2 items-center item-row">
 <input name="items[0][description]" placeholder="Description" required class="col-span-5 rounded-lg border border-gray-300 px-3 py-2 text-sm">
 <input name="items[0][quantity]" type="number" value="1" min="1" required class="col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm">
 <input name="items[0][unit_price]" type="number" step="0.01" value="0" min="0" required class="col-span-3 rounded-lg border border-gray-300 px-3 py-2 text-sm">
 <button type="button" onclick="this.closest('.item-row').remove()" class="col-span-2 text-red-500 text-sm">Remove</button>
 </div>
 </div>
 <button type="button" onclick="addItem()" class="mt-3 text-sm text-indigo-600 font-medium">+ Add Item</button>
 </div>

 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Discount</label><input name="discount" type="number" step="0.01" value="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Tax</label><input name="tax" type="number" step="0.01" value="0" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Terms & Conditions</label><textarea name="terms" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>

 <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-xs text-yellow-700">
 After creation, the invoice must be <strong>locked</strong> by an authorized finance user. Locking captures the exchange rate and makes the invoice immutable.
 </div>

 <div class="flex items-center gap-3">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg transition-colors">Create Invoice</button>
 <a href="{{ route('erp.invoices.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection
@push('scripts')
<script>
let itemIndex = 1;
function addItem(description = '', price = 0, quantity = 1) {
 const html = `<div class="grid grid-cols-12 gap-2 items-center item-row">
 <input name="items[${itemIndex}][description]" value="${description.replace(/"/g, '&quot;')}" placeholder="Description" required class="col-span-5 rounded-lg border border-gray-300 px-3 py-2 text-sm">
 <input name="items[${itemIndex}][quantity]" type="number" value="${quantity}" min="1" required class="col-span-2 rounded-lg border border-gray-300 px-3 py-2 text-sm">
 <input name="items[${itemIndex}][unit_price]" type="number" step="0.01" value="${price}" min="0" required class="col-span-3 rounded-lg border border-gray-300 px-3 py-2 text-sm">
 <button type="button" onclick="this.closest('.item-row').remove()" class="col-span-2 text-red-500 text-sm">Remove</button>
 </div>`;
 document.getElementById('items').insertAdjacentHTML('beforeend', html);
 itemIndex++;
}
function addPackage(sel) {
 const opt = sel.options[sel.selectedIndex];
 if (!opt || !opt.value) return;
 let lines = [];
 try { lines = JSON.parse(opt.dataset.items || '[]'); } catch (e) { lines = []; }
 if (!lines.length) lines = [{description: opt.dataset.name, quantity: 1, unit_price: opt.dataset.price}];
 lines.forEach((line) => addItem(line.description || '', line.unit_price || 0, line.quantity || 1));
 sel.selectedIndex = 0;
}
</script>
@endpush