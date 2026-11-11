@extends('erp.layouts.app') @section('title', 'Create Employee')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Create Employee Record</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.hr.store') }}" class="space-y-4">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">User *</label><select name="user_id" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="">Select user...</option>@foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }} ({{ $u->email }})</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label><input type="text" name="first_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label><input type="text" name="last_name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label><select name="employment_type" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="full_time">Full Time</option><option value="part_time">Part Time</option><option value="contract">Contract</option></select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="active">Active</option><option value="inactive">Inactive</option></select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Joining Date</label><input type="date" name="joining_date" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label><input type="date" name="date_of_birth" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label><input type="text" name="emergency_contact" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Emergency Phone</label><input type="text" name="emergency_phone" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 </div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Create Employee</button>
 <a href="{{ route('erp.hr.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection