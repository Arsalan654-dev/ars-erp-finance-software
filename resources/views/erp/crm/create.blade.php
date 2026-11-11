@extends('erp.layouts.app') @section('title', 'Create Client')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Create Client</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.clients.store') }}" class="space-y-4">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Business Name *</label><input type="text" name="business_name" value="{{ old('business_name') }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm focus:ring-indigo-500"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Legal Name</label><input type="text" name="legal_name" value="{{ old('legal_name') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Email</label><input type="email" name="email" value="{{ old('email') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Country</label><input type="text" name="country" value="{{ old('country') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="active">Active</option><option value="inactive">Inactive</option><option value="lead">Lead</option></select></div>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">{{ old('address') }}</textarea></div>
 <hr class="my-2">
 <p class="text-xs text-gray-500 ">Sensitive fields below are encrypted at rest (AES-256).</p>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">EIN</label><input type="text" name="ein" value="{{ old('ein') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">ITIN</label><input type="text" name="itin" value="{{ old('itin') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">UTR</label><input type="text" name="utr" value="{{ old('utr') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">SSN</label><input type="text" name="ssn" value="{{ old('ssn') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Tax Type</label><input type="text" name="tax_type" value="{{ old('tax_type') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">{{ old('notes') }}</textarea></div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg transition-colors">Create Client</button>
 <a href="{{ route('erp.clients.index') }}" class="px-6 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold rounded-lg transition-colors">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection