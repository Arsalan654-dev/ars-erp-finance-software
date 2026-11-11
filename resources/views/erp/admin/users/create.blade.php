@extends('erp.layouts.app') @section('title', 'Create User')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Create User</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.admin.users.store') }}" class="space-y-4">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label><input type="text" name="name" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Email *</label><input type="email" name="email" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Password *</label><input type="password" name="password" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label><input type="password" name="password_confirmation" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Department</label><input type="text" name="department" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Designation</label><input type="text" name="designation" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Roles</label><select name="roles[]" multiple required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach($roles as $r)<option value="{{ $r->id }}">{{ ucwords(str_replace('_',' ',$r->name)) }}</option>@endforeach</select></div>
 <div class="md:col-span-2 rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-xs text-blue-700">
     💡 To set this user's permissions, go to <strong>Roles &amp; Permissions</strong> after creating the user → click <strong>Set Permissions</strong> on their role → choose this user from the dropdown.
 </div>
 </div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Create User</button>
 <a href="{{ route('erp.admin.users') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection