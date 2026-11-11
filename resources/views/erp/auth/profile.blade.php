@extends('erp.layouts.app')
@section('title', 'My Profile')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">My Profile</h1>
        <p class="ks-page-sub">Manage your account details.</p>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;max-width:900px;">
    {{-- Profile Info --}}
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title">Profile Information</p>
        </div>
        <div class="ks-card-body">
            @if($errors->any())
                <div class="ks-alert ks-alert-error">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
                <div class="ks-alert ks-alert-success">{{ session('success') }}</div>
            @endif

            <form method="POST" action="{{ route('erp.profile.update') }}" class="space-y-4">
                @csrf
                @method('PATCH')

                <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px;">
                    <div class="consultiser-gradient" style="width:64px;height:64px;border-radius:16px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:700;flex-shrink:0;">
                        {{ strtoupper(substr($user->name, 0, 2)) }}
                    </div>
                    <div>
                        <p style="font-weight:600;color:var(--gray-900);margin:0;">{{ $user->name }}</p>
                        <p style="font-size:13px;color:var(--gray-400);margin:0;">{{ $user->email }}</p>
                        @if($user->roles->count())
                            <div style="display:flex;gap:6px;margin-top:6px;flex-wrap:wrap;">
                                @foreach($user->roles as $role)
                                    <span class="ks-badge ks-badge-indigo">{{ ucwords(str_replace('_',' ',$role->name)) }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="ks-form-label">Full Name</label>
                    <input type="text" name="name" value="{{ old('name', $user->name) }}" required class="ks-form-input">
                </div>

                <div>
                    <label class="ks-form-label">Email Address</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required class="ks-form-input">
                </div>

                <div>
                    <label class="ks-form-label">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" class="ks-form-input" placeholder="+92 300 1234567">
                </div>

                <div>
                    <button type="submit" class="ks-btn ks-btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Account Details --}}
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title">Account Details</p>
        </div>
        <div class="ks-card-body">
            <div class="space-y-4">
                <div>
                    <p style="font-size:12px;color:var(--gray-400);margin:0 0 4px;">Employee ID</p>
                    <p style="font-weight:600;color:var(--gray-700);margin:0;">{{ $user->employee_id ?? '—' }}</p>
                </div>
                <div>
                    <p style="font-size:12px;color:var(--gray-400);margin:0 0 4px;">Department</p>
                    <p style="font-weight:600;color:var(--gray-700);margin:0;">{{ ucwords(str_replace('_',' ',$user->department ?? '')) ?: '—' }}</p>
                </div>
                <div>
                    <p style="font-size:12px;color:var(--gray-400);margin:0 0 4px;">Designation</p>
                    <p style="font-weight:600;color:var(--gray-700);margin:0;">{{ ucwords(str_replace('_',' ',$user->designation ?? '')) ?: '—' }}</p>
                </div>
                <div>
                    <p style="font-size:12px;color:var(--gray-400);margin:0 0 4px;">Two-Factor Auth</p>
                    <p style="font-weight:600;color:var(--gray-700);margin:0;">{{ $user->two_factor_enabled ? 'Enabled' : 'Disabled' }}</p>
                </div>
                <div>
                    <p style="font-size:12px;color:var(--gray-400);margin:0 0 4px;">Account Status</p>
                    <span class="ks-badge {{ $user->is_active ? 'ks-badge-green' : 'ks-badge-red' }}">{{ $user->is_active ? 'Active' : 'Deactivated' }}</span>
                </div>
                <div>
                    <p style="font-size:12px;color:var(--gray-400);margin:0 0 4px;">Last Activity</p>
                    <p style="font-weight:600;color:var(--gray-700);margin:0;">{{ $user->last_activity_at?->diffForHumans() ?? '—' }}</p>
                </div>

                <div style="padding-top:16px;border-top:1px solid var(--gray-100);">
                    <a href="{{ route('erp.password.change') }}" class="ks-btn ks-btn-outline" style="font-size:13px;">
                        <i class="ti ti-key" style="font-size:14px;"></i> Change Password
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
