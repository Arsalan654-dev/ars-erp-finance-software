@extends('erp.layouts.app') @section('title', 'Upload Document')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Upload Document to Private Vault</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.documents.store') }}" enctype="multipart/form-data" class="space-y-4">
 @csrf
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Document File *</label><input type="file" name="document_file" required accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Client</label><select name="client_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="">— None —</option>@foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->business_name }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Category</label><input type="text" name="category" placeholder="e.g. Tax Certificate, Registration" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Upload</button>
 <a href="{{ route('erp.documents.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection