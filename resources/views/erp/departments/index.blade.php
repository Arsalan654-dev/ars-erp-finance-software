@extends('erp.layouts.app')
@section('title', 'Departments')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Departments</h1>
        <p class="ks-page-sub">Manage departments and their members.</p>
    </div>
</div>

<div class="ks-grid-2" style="grid-template-columns:1fr 1fr;">
    {{-- Create Department --}}
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">Create Department</p>
        </div>
        <div class="ks-card-body">
            <form method="POST" action="{{ route('erp.departments.store') }}" class="space-y-3">
                @csrf
                <div>
                    <label class="ks-label">Name</label>
                    <input type="text" name="name" class="ks-form-input" placeholder="e.g. US TAX" required>
                </div>
                <div>
                    <label class="ks-label">Slug</label>
                    <input type="text" name="slug" class="ks-form-input" placeholder="e.g. us-tax" required>
                </div>
                <div>
                    <label class="ks-label">Head</label>
                    <select name="head_id" class="ks-form-input">
                        <option value="">No head assigned</option>
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:14px;"></i> Create</button>
            </form>
        </div>
    </div>

    {{-- Add Member --}}
    @if($departments->count())
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">Add Member to Department</p>
        </div>
        <div class="ks-card-body">
            <form method="POST" action="" id="addMemberForm" class="space-y-3">
                @csrf
                <div>
                    <label class="ks-label">Department</label>
                    <select name="department_id" class="ks-form-input" id="deptSelect" onchange="document.getElementById('addMemberForm').action='/erp/departments/' + this.value + '/members'">
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">User</label>
                    <select name="user_id" class="ks-form-input">
                        @foreach($users as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Role</label>
                    <select name="role_in_department" class="ks-form-input">
                        <option value="member">Member</option>
                        <option value="head">Head</option>
                        <option value="viewer">Viewer</option>
                    </select>
                </div>
                <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Add Member</button>
            </form>
        </div>
    </div>
    @endif
</div>

{{-- Departments Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Departments</p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Slug</th>
                        <th>Head</th>
                        <th style="text-align:center;">Members</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $dept)
                    <tr>
                        <td class="ks-strong">{{ $dept->name }}</td>
                        <td><span class="ks-badge ks-badge-blue">{{ strtoupper(substr($dept->slug, 0, 4)) }}</span></td>
                        <td>{{ $dept->head?->name ?? '—' }}</td>
                        <td style="text-align:center;">{{ $dept->members->count() }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:40px;">No departments found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $departments->links() }}</div>
</div>

{{-- Department Members --}}
@foreach($departments as $dept)
@if($dept->members->count())
<div class="ks-card" style="margin-top:16px;">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">{{ $dept->name }} — Members</p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr><th>User</th><th style="text-align:center;">Role</th><th style="text-align:right;">Action</th></tr>
                </thead>
                <tbody>
                    @foreach($dept->members as $member)
                    <tr>
                        <td class="ks-strong">{{ $member->name }}</td>
                        <td style="text-align:center;">{!! erp_status_badge($member->pivot->role_in_department) !!}</td>
                        <td style="text-align:right;">
                            <form method="POST" action="{{ route('erp.departments.members.destroy', [$dept, $member]) }}" class="inline" onsubmit="return confirm('Remove this member?')">
                                @csrf @method('DELETE')
                                <button class="ks-btn ks-btn-light ks-btn-sm" style="color:var(--red-500);">Remove</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif
@endforeach
@endsection
