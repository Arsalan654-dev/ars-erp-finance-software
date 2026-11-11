@extends('erp.layouts.app')
@section('title', 'Edit Project')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Edit Project</h1>
        <p class="ks-page-sub">Update project information.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.projects.show', $project) }}" class="ks-btn ks-btn-light"><i class="ti ti-arrow-left" style="font-size:14px;"></i> Back</a>
    </div>
</div>

<div class="ks-card" style="max-width:720px;">
    <div class="ks-card-body" style="padding:28px 32px;">
        <form method="POST" action="{{ route('erp.projects.update', $project) }}" class="space-y-5">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="ks-label">Title *</label>
                    <input type="text" name="title" value="{{ old('title', $project->title) }}" class="ks-form-input" required>
                </div>
                <div class="col-span-2">
                    <label class="ks-label">Description</label>
                    <textarea name="description" rows="3" class="ks-form-input">{{ old('description', $project->description) }}</textarea>
                </div>
                <div>
                    <label class="ks-label">Department *</label>
                    <select name="department" class="ks-form-input" required>
                        @foreach(config('erp.departments') as $d)
                        <option value="{{ $d }}" @selected(old('department', $project->department)===$d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Priority *</label>
                    <select name="priority" class="ks-form-input" required>
                        @foreach(['low','medium','high','urgent'] as $p)
                        <option value="{{ $p }}" @selected(old('priority', $project->priority)===$p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Start Date</label>
                    <input type="date" name="start_date" value="{{ old('start_date', $project->start_date?->format('Y-m-d')) }}" class="ks-form-input">
                </div>
                <div>
                    <label class="ks-label">Due Date</label>
                    <input type="date" name="due_date" value="{{ old('due_date', $project->due_date?->format('Y-m-d')) }}" class="ks-form-input">
                </div>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="ks-btn ks-btn-primary"><i class="ti ti-check" style="font-size:14px;"></i> Update Project</button>
                <a href="{{ route('erp.projects.show', $project) }}" class="ks-btn ks-btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
