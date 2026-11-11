@extends('erp.layouts.app') @section('title', 'Edit User')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Edit User: {{ $user->name }}</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.admin.users.update', $user) }}" class="space-y-4">
 @csrf @method('PATCH')
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label><input type="text" name="name" value="{{ old('name', $user->name) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Email *</label><input type="email" name="email" value="{{ old('email', $user->email) }}" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Phone</label><input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Department</label><input type="text" name="department" value="{{ old('department', $user->department) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Designation</label><input type="text" name="designation" value="{{ old('designation', $user->designation) }}" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div class="flex items-center gap-3"><label class="flex items-center text-sm text-gray-700 "><input type="checkbox" name="is_active" value="1" @checked($user->is_active) class="mr-2"> Active</label></div>
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Roles</label><select name="roles[]" multiple class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach($roles as $r)<option value="{{ $r->id }}" @selected($user->roles->pluck('id')->contains($r->id))>{{ ucwords(str_replace('_',' ',$r->name)) }}</option>@endforeach</select></div>
 <div class="md:col-span-2 rounded-lg bg-blue-50 border border-blue-100 px-4 py-3 text-xs text-blue-700">
     💡 To change this user's permissions, go to <strong>Roles &amp; Permissions</strong> → click <strong>Set Permissions</strong> on their role → choose this user from the dropdown.
 </div>
 </div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Update User</button>
 <a href="{{ route('erp.admin.users') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection