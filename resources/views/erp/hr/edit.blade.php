@extends('erp.layouts.app') @section('title', 'Edit Employee')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Edit Employee: {{ $employee->first_name }} {{ $employee->last_name }}</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.hr.update', $employee) }}" class="space-y-4">
 @csrf @method('PATCH')
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label><input type="text" name="first_name" value="{{ old('first_name', $employee->first_name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label><input type="text" name="last_name" value="{{ old('last_name', $employee->last_name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Employment Type</label><select name="employment_type" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach(['full_time','part_time','contract'] as $t)<option value="{{ $t }}" @selected($employee->employment_type===$t)>{{ ucwords(str_replace('_',' ',$t)) }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Status</label><select name="status" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach(['active','inactive','terminated'] as $s)<option value="{{ $s }}" @selected($employee->status===$s)>{{ ucfirst($s) }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Joining Date</label><input type="date" name="joining_date" value="{{ $employee->joining_date?->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label><input type="date" name="date_of_birth" value="{{ $employee->date_of_birth?->format('Y-m-d') }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Address</label><textarea name="address" rows="2" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">{{ $employee->address }}</textarea></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Emergency Contact</label><input type="text" name="emergency_contact" value="{{ $employee->emergency_contact }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Emergency Phone</label><input type="text" name="emergency_phone" value="{{ $employee->emergency_phone }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 </div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Update</button>
 <a href="{{ route('erp.hr.show', $employee) }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection