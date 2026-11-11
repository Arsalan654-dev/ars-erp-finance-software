@extends('erp.layouts.app') @section('title', 'New Compliance Record')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">New Compliance Record</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.compliance.store') }}" enctype="multipart/form-data" class="space-y-4">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Client *</label><select name="client_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->business_name }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Document Type *</label><input type="text" name="document_type" required placeholder="e.g. Tax Certificate" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" name="title" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Reference Number</label><input type="text" name="reference_number" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Issuing Authority</label><input type="text" name="issuing_authority" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label><input type="date" name="issue_date" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label><input type="date" name="expiry_date" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
  <div><label class="block text-sm font-medium text-gray-700 mb-1">Attach Document (PDF)</label><input type="file" name="document_file" accept=".pdf" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>
 <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-xs text-yellow-700">The renewal system automatically flags records at 60/30 days before expiry and alerts at the due date.</div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Create Record</button>
 <a href="{{ route('erp.compliance.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection