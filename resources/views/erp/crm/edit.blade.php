@extends('erp.layouts.app') @section('title', 'Edit Client')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Edit Client: {{ $client->business_name }}</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.clients.update', $client) }}" class="space-y-4">
 @csrf @method('PATCH')
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label><input type="text" name="business_name" value="{{ old('business_name', $client->business_name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Legal Name</label><input type="text" name="legal_name" value="{{ old('legal_name', $client->legal_name) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" value="{{ old('email', $client->email) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" value="{{ old('phone', $client->phone) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Country</label><input type="text" name="country" value="{{ old('country', $client->country) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">
 @foreach(['active','inactive','lead'] as $s)<option value="{{ $s }}" @selected(old('status', $client->status) === $s)>{{ ucfirst($s) }}</option>@endforeach
 </select></div>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">{{ old('address', $client->address) }}</textarea></div>
 <hr>
 <p class="text-xs text-gray-500 ">Leave blank to keep existing encrypted value. Changes are re-encrypted at rest.</p>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">EIN</label><input type="text" name="ein" value="" placeholder="Enter new EIN to change" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">ITIN</label><input type="text" name="itin" value="" placeholder="Enter new ITIN to change" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">UTR</label><input type="text" name="utr" value="" placeholder="Enter new UTR to change" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">SSN</label><input type="text" name="ssn" value="" placeholder="Enter new SSN to change" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Tax Type</label><input type="text" name="tax_type" value="{{ old('tax_type', $client->tax_type) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">{{ old('notes', $client->notes) }}</textarea></div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg transition-colors">Update Client</button>
 <a href="{{ route('erp.clients.show', $client) }}" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection