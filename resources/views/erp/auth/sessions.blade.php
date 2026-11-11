@extends('erp.layouts.app')

@section('title', 'Active Sessions')

@section('content')
<div style="max-width:640px;margin:0 auto;">
 <div class="ks-page-header">
  <h1 class="ks-page-title">Session Security</h1>
  <form method="POST" action="{{ route('erp.sessions.logout-other') }}">
   @csrf
   <button type="submit" class="ks-btn" style="background:#dc2626;color:#fff;border-color:#dc2626;">Logout Other Sessions</button>
  </form>
 </div>

 <div class="ks-card">
  <div class="ks-card-body">
   <div class="space-y-4">
    <div style="display:flex;gap:12px;padding:16px;border-radius:10px;background:#e8f1ff;border:1px solid #bfd9fc;">
     <svg style="width:24px;height:24px;color:#1D4ED8;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
     <div style="font-size:13px;">
      <p style="font-weight:600;color:#1D4ED8;margin:0 0 4px;">Single Active Session</p>
      <p style="color:#2563EB;margin:0;">This system enforces one active session per account. If you sign in from a new device, your previous session will be terminated.</p>
     </div>
    </div>

    <div style="display:flex;gap:12px;padding:16px;border-radius:10px;background:#fef6e0;border:1px solid #fde68a;">
     <svg style="width:24px;height:24px;color:#d97706;flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
     <div style="font-size:13px;">
      <p style="font-weight:600;color:#92400e;margin:0 0 4px;">Idle Session Timeout</p>
      <p style="color:#a16207;margin:0;">Sessions expire automatically after {{ config('erp.session_idle_timeout', 30) }} minutes of inactivity.</p>
     </div>
    </div>

    <div style="margin-top:8px;">
     <p style="font-size:13px;font-weight:600;color:var(--gray-600);margin:0 0 8px;">Current Session</p>
     <div style="border-radius:10px;border:1px solid var(--gray-200);padding:16px;display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:12px;background:#fff;">
      <div style="min-width:0;flex:1;">
       <p style="font-weight:600;color:var(--gray-800);margin:0;font-size:14px;">This device</p>
       <p style="font-size:12px;color:var(--gray-500);word-break:break-all;margin:4px 0 0;">{{ request()->userAgent() }}</p>
      </div>
      <span style="font-size:12px;font-weight:600;color:#16a34a;background:#e6f9ef;border:1px solid #b7ebcf;border-radius:50rem;padding:4px 12px;">Active Now</span>
     </div>
    </div>
   </div>
  </div>
 </div>

 {{-- Two-Factor Authentication --}}
 <div class="ks-card" style="margin-top:22px;">
  <div class="ks-card-body">
   <div style="display:flex;gap:12px;align-items:flex-start;">
    <svg style="width:24px;height:24px;color:var(--role-accent);flex-shrink:0;margin-top:2px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
    <div style="flex:1;min-width:0;">
     <div style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:10px;margin-bottom:4px;">
      <p style="font-weight:600;color:var(--gray-800);margin:0;font-size:14px;">Two-Factor Authentication (2FA)</p>
      <span style="font-size:12px;font-weight:600;border-radius:50rem;padding:4px 12px;{{ auth()->user()->two_factor_enabled ? 'color:#16a34a;background:#e6f9ef;border:1px solid #b7ebcf;' : 'color:var(--gray-500);background:var(--gray-100);border:1px solid var(--gray-200);' }}">{{ auth()->user()->two_factor_enabled ? 'Enabled' : 'Disabled' }}</span>
     </div>
     <p style="font-size:12px;color:var(--gray-500);margin:0;">Add an extra layer of security — you'll enter a code from your email on every login.</p>
     @if(auth()->user()->two_factor_enabled)
      <form method="POST" action="{{ route('erp.sessions.2fa') }}" style="margin-top:16px;">
       @csrf
       <input type="hidden" name="enable" value="0">
       <label style="display:block;font-size:12px;color:var(--gray-500);margin-bottom:6px;">Enter password to disable</label>
       <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <input type="password" name="password" required placeholder="Current password" class="ks-form-input" style="flex:1;min-width:120px;">
        <button type="submit" class="ks-btn" style="background:#dc2626;color:#fff;border-color:#dc2626;">Disable 2FA</button>
       </div>
      </form>
     @else
      <form method="POST" action="{{ route('erp.sessions.2fa') }}" style="margin-top:16px;">
       @csrf
       <input type="hidden" name="enable" value="1">
       <label style="display:block;font-size:12px;color:var(--gray-500);margin-bottom:6px;">Enter password to enable</label>
       <div style="display:flex;gap:8px;flex-wrap:wrap;">
        <input type="password" name="password" required placeholder="Current password" class="ks-form-input" style="flex:1;min-width:120px;">
        <button type="submit" class="ks-btn ks-btn-primary">Enable 2FA</button>
       </div>
      </form>
     @endif
    </div>
   </div>
  </div>
 </div>
</div>
@endsection
