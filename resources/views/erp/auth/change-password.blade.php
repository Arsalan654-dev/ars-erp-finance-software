@extends('erp.layouts.app')

@section('title', 'Change Password')

@section('content')
<div style="max-width:480px;margin:0 auto;">
 <div class="ks-page-header" style="flex-direction:column;align-items:flex-start;">
  <h1 class="ks-page-title">Change Password</h1>
 </div>

 <div class="ks-card">
  <div class="ks-card-body">
   <form method="POST" action="{{ route('erp.password.change.submit') }}" class="space-y-4">
    @csrf
    <div>
     <label class="ks-form-label">Current Password</label>
     <input type="password" name="current_password" required class="ks-form-input">
    </div>
    <div>
     <label class="ks-form-label">New Password</label>
     <input type="password" name="password" required class="ks-form-input">
    </div>
    <div>
     <label class="ks-form-label">Confirm New Password</label>
     <input type="password" name="password_confirmation" required class="ks-form-input">
    </div>
    <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;padding:12px;">Update Password</button>
   </form>
  </div>
 </div>
</div>
@endsection
