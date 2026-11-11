@extends('erp.layouts.app')
@section('title', 'Create Project')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Create Project</h1>
        <p class="ks-page-sub">Add a new project to the system.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.projects.index') }}" class="ks-btn ks-btn-light"><i class="ti ti-arrow-left" style="font-size:14px;"></i> Back</a>
    </div>
</div>

<div class="ks-card" style="max-width:720px;">
    <div class="ks-card-body" style="padding:28px 32px;">
        <form method="POST" action="{{ route('erp.projects.store') }}" class="space-y-5">
            @csrf
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="ks-label">Client *</label>
                    <select name="client_id" class="ks-form-input" required>
                        <option value="">Select client</option>
                        @foreach($clients as $c)
                        <option value="{{ $c->id }}" @selected(old('client_id')==$c->id)>{{ $c->business_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Invoice</label>
                    <select name="invoice_id" class="ks-form-input">
                        <option value="">No invoice</option>
                        @foreach($invoices as $inv)
                        <option value="{{ $inv->id }}" @selected(old('invoice_id')==$inv->id)>{{ $inv->invoice_number }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="ks-label">Title *</label>
                    <input type="text" name="title" value="{{ old('title') }}" class="ks-form-input" required placeholder="Project title">
                </div>
                <div class="col-span-2">
                    <label class="ks-label">Description</label>
                    <textarea name="description" rows="3" class="ks-form-input" placeholder="Project description...">{{ old('description') }}</textarea>
                </div>
                <div>
                    <label class="ks-label">Department *</label>
                    <select name="department" class="ks-form-input" required>
                        @foreach(config('erp.departments') as $d)
                        <option value="{{ $d }}" @selected(old('department')===$d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Priority *</label>
                    <select name="priority" class="ks-form-input" required>
                        @foreach(['low','medium','high','urgent'] as $p)
                        <option value="{{ $p }}" @selected(old('priority','medium')===$p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date') }}" class="ks-form-input">
                </div>
                <div>
                    <label class="ks-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date') }}" class="ks-form-input">
                </div>
                <div class="col-span-2">
                    <label class="ks-label">Team Members</label>
                    <select name="members[]" class="ks-form-input" multiple size="4">
                        @foreach($users as $u)
                        <option value="{{ $u->id }}" @selected(in_array($u->id, old('members', [])))>{{ $u->name }}</option>
                        @endforeach
                    </select>
                    <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Hold Ctrl/Cmd to select multiple</p>
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:14px;"></i> Create Project</button>
                <a href="{{ route('erp.projects.index') }}" class="ks-btn ks-btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
