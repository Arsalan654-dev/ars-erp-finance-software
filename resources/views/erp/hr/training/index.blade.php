@extends('erp.layouts.app')
@section('title', 'Training')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Training & Development</h1></div>
</div>
<div class="ks-grid-2">
 <div class="ks-card">
  <div class="ks-card-body">
   <h3 class="ks-card-title">Create Training Course</h3>
    @can('training.manage')
    <form method="POST" action="{{ route('erp.hr.training.store') }}" class="space-y-3">
     @csrf
     <input type="text" name="title" placeholder="Course title" required class="ks-form-input">
     <input type="text" name="category" placeholder="Category" class="ks-form-input">
     <input type="number" name="duration_hours" placeholder="Duration (hours)" min="1" class="ks-form-input">
     <textarea name="description" placeholder="Description" rows="3" class="ks-form-input"></textarea>
     <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Create</button>
    </form>
    @endcan
  </div>
 </div>
 <div class="ks-card">
  <div class="ks-card-body">
   <h3 class="ks-card-title">Assign Training</h3>
    @can('training.manage')
    <form method="POST" action="{{ route('erp.hr.training.assign') }}" class="space-y-3">
     @csrf
     <select name="training_course_id" required class="ks-form-input"><option value="">Select course...</option>@foreach(\App\Models\TrainingCourse::all() as $c)<option value="{{ $c->id }}">{{ $c->title }}</option>@endforeach</select>
     <select name="employee_id" required class="ks-form-input"><option value="">Select employee...</option>@foreach(\App\Models\Employee::with('user')->get() as $e)<option value="{{ $e->id }}">{{ $e->first_name }} {{ $e->last_name }}</option>@endforeach</select>
     <button type="submit" class="ks-btn" style="background:#16a34a;color:#fff;border-color:#16a34a;">Assign</button>
    </form>
    @endcan
  </div>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead><tr><th>Course</th><th>Category</th><th style="text-align:center;">Assignments</th><th>Created</th></tr></thead>
    <tbody>
    @forelse($courses as $c)
     <tr>
      <td data-label="Course" style="font-weight:600;">{{ $c->title }}</td>
      <td data-label="Category">{{ $c->category }}</td>
      <td data-label="Assignments" style="text-align:center;">{{ $c->assignments_count }}</td>
      <td data-label="Created" style="color:var(--gray-500);">{{ $c->created_at->format('Y-m-d') }}</td>
     </tr>
    @empty <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:40px;">No training courses.</td></tr> @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $courses->links() }}</div>
</div>
@endsection
