@extends('erp.layouts.app') @section('title', 'Edit Compliance Record')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Edit Compliance Record</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.compliance.update', $record) }}" class="space-y-4">
 @csrf @method('PATCH')
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Client *</label><select name="client_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach($clients as $c)<option value="{{ $c->id }}" @selected($record->client_id === $c->id)>{{ $c->business_name }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Document Type *</label><input type="text" name="document_type" value="{{ $record->document_type }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Title</label><input type="text" name="title" value="{{ $record->title }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Reference</label><input type="text" name="reference_number" value="{{ $record->reference_number }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Issuing Authority</label><input type="text" name="issuing_authority" value="{{ $record->issuing_authority }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Issue Date</label><input type="date" name="issue_date" value="{{ $record->issue_date?->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Expiry Date</label><input type="date" name="expiry_date" value="{{ $record->expiry_date?->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach(['active','expired','renewed'] as $s)<option value="{{ $s }}" @selected($record->status === $s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
 </div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Notes</label><textarea name="notes" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">{{ $record->notes }}</textarea></div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Update</button>
 <a href="{{ route('erp.compliance.show', $record) }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection