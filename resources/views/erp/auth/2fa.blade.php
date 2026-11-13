@extends('erp.layouts.blank') @section('title', 'Two-Factor Verification')
@section('content')
<div class="min-h-screen flex items-center justify-center bg-ink p-4 sm:p-6 relative overflow-hidden">
 <div class="absolute -top-40 -right-40 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl" style="pointer-events:none;"></div>
 <div class="absolute -bottom-40 -left-40 w-[500px] h-[500px] rounded-full role-bg-soft blur-3xl" style="pointer-events:none;"></div>
 <div class="relative z-10 w-full" style="max-width:420px;">
  <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:32px;">
   <div class="consultiser-gradient" style="width:48px;height:48px;border-radius:14px;display:flex;align-items:center;justify-content:center;padding:8px;border:1px solid rgba(255,255,255,0.1);">
    <img alt="ARS ERP Finance Software" class="w-full h-full object-contain" src="{{ asset('erp-assets/ars-erp-icon.png') }}"/>
   </div>
   <span style="font-size:20px;font-weight:700;color:#fff;letter-spacing:-0.02em;">ARS ERP <span class="consultiser-gradient-text">Finance Software</span></span>
  </div>

  <div class="ks-card" style="border-radius:16px;padding:32px;border:1px solid rgba(255,255,255,0.1);background:#fff;">
   <div class="consultiser-gradient" style="width:56px;height:56px;border-radius:14px;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
    <svg style="width:28px;height:28px;color:#fff;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
   </div>
   <h2 style="font-size:20px;font-weight:700;color:var(--gray-900);text-align:center;margin:0 0 8px;">Two-Factor Verification</h2>
   <p style="font-size:13px;color:var(--gray-400);text-align:center;margin:0 0 24px;">Enter the 6-digit code sent to your email to complete sign in.</p>

   @if($errors->any())
    <div class="ks-alert ks-alert-error">{{ $errors->first() }}</div>
   @endif

   <form method="POST" action="{{ route('erp.login.2fa.submit') }}" class="space-y-4">
    @csrf
    <input type="text" name="code" inputmode="numeric" maxlength="6" autocomplete="one-time-code" autofocus required
     placeholder="••••••"
     class="ks-form-input" style="text-align:center;font-size:24px;font-weight:700;letter-spacing:0.5em;padding:12px;">
    <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;padding:12px;">
     Verify & Sign In
    </button>
   </form>

   <a href="{{ route('erp.login') }}" style="display:block;text-align:center;font-size:13px;color:var(--gray-400);margin-top:16px;text-decoration:none;" class="role-hover-text">← Back to login</a>
  </div>
 </div>
</div>
@endsection
