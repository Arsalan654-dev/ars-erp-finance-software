@extends('erp.layouts.blank')

@section('title', 'Reset Password')

@section('content')
<div class="min-h-screen w-full flex items-center justify-center bg-light px-4 py-6 relative overflow-hidden">
    <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl opacity-50" style="pointer-events:none;"></div>
    <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl opacity-50" style="pointer-events:none;"></div>

    <div class="relative z-10 w-full max-w-md">
        <div class="ks-card" style="border-radius:16px;">
            <div class="ks-card-body" style="padding:32px;">
                <div style="text-align:center;margin-bottom:28px;">
                    <div class="consultiser-gradient" style="width:56px;height:56px;border-radius:16px;display:flex;align-items:center;justify-content:center;margin:0 auto 16px;">
                        <i class="ti ti-key" style="font-size:24px;color:#fff;"></i>
                    </div>
                    <h2 style="font-size:22px;font-weight:700;color:var(--gray-900);margin:0 0 4px;">Reset Password</h2>
                    <p style="font-size:13px;color:var(--gray-400);">Enter your new password below.</p>
                </div>

                @if($errors->any())
                    <div class="ks-alert ks-alert-error">{{ $errors->first() }}</div>
                @endif

                <form method="POST" action="{{ route('erp.password.reset.submit') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <div>
                        <label class="ks-form-label">Email Address</label>
                        <div style="position:relative;">
                            <i class="ti ti-mail" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                            <input type="email" name="email" value="{{ old('email') }}" required autofocus class="ks-form-input" style="padding-left:38px;" placeholder="you@consultiser.com">
                        </div>
                    </div>

                    <div>
                        <label class="ks-form-label">New Password</label>
                        <div style="position:relative;">
                            <i class="ti ti-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                            <input type="password" name="password" required class="ks-form-input" style="padding-left:38px;" placeholder="Min 8 characters">
                        </div>
                    </div>

                    <div>
                        <label class="ks-form-label">Confirm New Password</label>
                        <div style="position:relative;">
                            <i class="ti ti-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                            <input type="password" name="password_confirmation" required class="ks-form-input" style="padding-left:38px;" placeholder="Repeat password">
                        </div>
                    </div>

                    <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;padding:12px;font-size:14px;">
                        Reset Password
                    </button>
                </form>

                <div style="text-align:center;margin-top:20px;">
                    <a href="{{ route('erp.login') }}" style="font-size:13px;color:var(--gray-500);" class="role-text">
                        <i class="ti ti-arrow-left" style="font-size:13px;"></i> Back to Login
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
