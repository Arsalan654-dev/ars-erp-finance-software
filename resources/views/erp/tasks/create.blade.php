@extends('erp.layouts.app') @section('title', 'Create Task')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">Create Task</h1>
 <div class="ks-card"><div class="ks-card-body">
 <form method="POST" action="{{ route('erp.tasks.store') }}" class="space-y-4">
 @csrf
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Title *</label><input type="text" name="title" required class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Client</label><select name="client_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="">— None —</option>@foreach($clients as $c)<option value="{{ $c->id }}" @selected(request('client') == $c->id)>{{ $c->business_name }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Project</label><select name="project_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="">— None —</option>@foreach(\App\Models\Project::whereIn('status', ['pending','in_progress','review'])->get() as $p)<option value="{{ $p->id }}" @selected(request('project_id') == $p->id)>{{ $p->title }} ({{ $p->project_number }})</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Department</label><select name="department" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="">— None —</option>@foreach(['US TAX','UK TAX','LEGAL','GENERAL'] as $d)<option value="{{ $d }}">{{ $d }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Priority</label><select name="priority" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm">@foreach(['low','medium','high','urgent'] as $pr)<option value="{{ $pr }}" @selected($pr==='medium')>{{ ucfirst($pr) }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Assign To</label><select name="assignee_id" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"><option value="">— Unassigned —</option>@foreach($assignees as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label><input type="date" name="start_date" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Due Date</label><input type="date" name="due_date" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></div>
 <div class="md:col-span-2"><label class="block text-sm font-medium text-gray-700 mb-1">Description</label><textarea name="description" rows="3" class="w-full rounded-lg border border-gray-300 px-4 py-2.5 text-sm"></textarea></div>
 </div>
 <div class="flex items-center gap-3 pt-2">
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Create Task</button>
 <a href="{{ route('erp.tasks.index') }}" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold rounded-lg">Cancel</a>
 </div>
 </form>
 </div>
</div></div>@endsection