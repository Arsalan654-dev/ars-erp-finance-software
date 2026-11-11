@extends('erp.layouts.app')
@section('title', 'Users')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">User Management</h1>
        <p class="ks-page-sub">Manage system users, roles and access.</p>
    </div>
    <div class="ks-page-actions">
        @can('user.manage')
        <a href="{{ route('erp.admin.users.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> New User</a>
        @endcan
    </div>
</div>

<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Users <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $users->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Roles</th>
                        <th style="text-align:center;">Active</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $u)
                        <tr>
                            <td class="ks-strong">{{ $u->name }}</td>
                            <td>{{ $u->email }}</td>
                            <td>{{ $u->department ?? '—' }}</td>
                            <td>
                                @foreach($u->roles as $r)<span class="ks-badge ks-badge-indigo" style="margin-right:4px;">{{ ucwords(str_replace('_',' ',$r->name)) }}</span>@endforeach
                            </td>
                            <td style="text-align:center;">
                                @if($u->is_active)
                                    <span style="width:10px;height:10px;border-radius:50%;background:#16a34a;display:inline-block;"></span>
                                @else
                                    <span style="width:10px;height:10px;border-radius:50%;background:#dc2626;display:inline-block;"></span>
                                @endif
                            </td>
                            <td style="text-align:right;">
                                @can('user.manage')
                                <a href="{{ route('erp.admin.users.edit', $u) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-edit" style="font-size:14px;"></i> Edit</a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:var(--gray-400);padding:40px;">No users.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $users->links() }}
    </div>
</div>
@endsection