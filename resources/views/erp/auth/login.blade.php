@extends('erp.layouts.blank')

@section('title', 'Login')

@section('content')
<div class="min-h-screen w-full flex items-center justify-center bg-light px-4 py-6 relative overflow-hidden">
    {{-- Ambient glows --}}
    <div class="absolute -top-32 -left-32 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl opacity-50" style="pointer-events:none;"></div>
    <div class="absolute -bottom-32 -right-32 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl opacity-50" style="pointer-events:none;"></div>

    <div class="relative z-10 w-full max-w-4xl">
        {{-- ===== Mobile & Tablet (< lg) ===== --}}
        <div class="lg:hidden" x-data="{ showForm: true }">
            <div class="ks-card" style="border-radius:16px;">
                <div class="ks-card-body" style="padding:28px 24px;">
                    <div style="text-align:center;margin-bottom:24px;">
                        <div class="consultiser-gradient" style="width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 12px;">
                            <img alt="ARS ERP Finance Software" class="w-8 h-8 object-contain" src="{{ asset('erp-assets/ars-erp-icon.png') }}"/>
                        </div>
                        <h2 style="font-size:22px;font-weight:700;color:var(--gray-900);margin:0 0 4px;">Welcome back</h2>
                        <p style="font-size:13px;color:var(--gray-400);">Sign in to continue to your workspace</p>
                    </div>

                    @if($errors->any())
                        <div class="ks-alert ks-alert-error">{{ $errors->first() }}</div>
                    @endif
                    @if(session('success'))
                        <div class="ks-alert ks-alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('erp.login.submit') }}" class="space-y-3.5">
                        @csrf
                        <div>
                            <label class="ks-form-label">Email Address</label>
                            <div style="position:relative;">
                                <i class="ti ti-mail" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="ks-form-input" style="padding-left:38px;" placeholder="you@example.com">
                            </div>
                        </div>
                        <div>
                            <label class="ks-form-label">Password</label>
                            <div style="position:relative;">
                                <i class="ti ti-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                                <input type="password" name="password" required class="ks-form-input" style="padding-left:38px;" placeholder="Enter your password">
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                            <label style="display:flex;align-items:center;color:var(--gray-600);cursor:pointer;">
                                <input type="checkbox" name="remember" style="margin-right:8px;border-radius:4px;"> Remember me
                            </label>
                            <a href="{{ route('erp.password.forgot') }}" class="role-text" style="font-size:13px;">Forgot password?</a>
                        </div>
                        <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;padding:12px;font-size:14px;">
                            Sign In
                        </button>
                    </form>
                    <p style="font-size:12px;color:var(--gray-400);text-align:center;margin-top:24px;">Contact your administrator for account access.</p>
                </div>
            </div>
        </div>

        {{-- ===== Desktop (lg+) ===== --}}
        <div class="hidden lg:grid" style="grid-template-columns:1fr 1.3fr;gap:32px;align-items:center;">
            {{-- Left: branding --}}
            <div style="text-align:center;">
                <div class="consultiser-gradient" style="width:64px;height:64px;border-radius:18px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;box-shadow:0 12px 30px var(--role-accent-soft);">
                    <img alt="ARS ERP Finance Software" class="w-10 h-10 object-contain" src="{{ asset('erp-assets/ars-erp-icon.png') }}"/>
                </div>
                <h2 style="font-size:24px;font-weight:800;color:var(--gray-900);margin:0 0 8px;letter-spacing:-0.02em;">ARS ERP <span class="consultiser-gradient-text">Finance Software</span></h2>
                <p style="font-size:13px;color:var(--gray-400);max-width:280px;margin:0 auto 28px;">The unified platform for ARS ERP's corporate operations and compliance management.</p>
                <p style="font-size:12px;color:var(--gray-400);margin-top:20px;">Contact your administrator for account access.</p>
            </div>

            {{-- Right: form card --}}
            <div class="ks-card" style="border-radius:16px;">
                <div class="ks-card-body" style="padding:32px;">
                    <div style="text-align:center;margin-bottom:28px;">
                        <h2 style="font-size:22px;font-weight:700;color:var(--gray-900);margin:0 0 4px;">Welcome back</h2>
                        <p style="font-size:13px;color:var(--gray-400);">Sign in to continue to your workspace</p>
                    </div>

                    @if($errors->any())
                        <div class="ks-alert ks-alert-error">{{ $errors->first() }}</div>
                    @endif
                    @if(session('success'))
                        <div class="ks-alert ks-alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="POST" action="{{ route('erp.login.submit') }}" class="space-y-4">
                        @csrf
                        <div>
                            <label class="ks-form-label">Email Address</label>
                            <div style="position:relative;">
                                <i class="ti ti-mail" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                                <input type="email" name="email" value="{{ old('email') }}" required autofocus class="ks-form-input" style="padding-left:38px;" placeholder="you@example.com">
                            </div>
                        </div>
                        <div>
                            <label class="ks-form-label">Password</label>
                            <div style="position:relative;">
                                <i class="ti ti-lock" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                                <input type="password" name="password" required class="ks-form-input" style="padding-left:38px;" placeholder="Enter your password">
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;justify-content:space-between;font-size:13px;">
                            <label style="display:flex;align-items:center;color:var(--gray-600);cursor:pointer;">
                                <input type="checkbox" name="remember" style="margin-right:8px;border-radius:4px;"> Remember me
                            </label>
                            <a href="{{ route('erp.password.forgot') }}" class="role-text" style="font-size:13px;">Forgot password?</a>
                        </div>
                        <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;padding:12px;font-size:14px;">
                            Sign In
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    [x-cloak] { display: none !important; }
</style>
@endsection
