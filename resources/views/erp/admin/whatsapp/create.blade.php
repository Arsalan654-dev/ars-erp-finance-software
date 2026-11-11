@extends('erp.layouts.app')
@section('title', 'Add WhatsApp Account')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Add WhatsApp Account</h1></div>
 <div class="ks-page-actions">
  <a href="{{ route('erp.admin.whatsapp.index') }}" class="ks-btn ks-btn-light"><i class="ti ti-arrow-left" style="font-size:14px;"></i> Back</a>
 </div>
</div>

<div class="ks-card" style="max-width:720px;">
 <div class="ks-card-body" style="padding:28px 32px;">
  <form method="POST" action="{{ route('erp.admin.whatsapp.store') }}" class="space-y-5">
   @csrf
   <div class="grid grid-cols-2 gap-4">
    <div>
     <label class="ks-label">Account Name</label>
     <input type="text" name="name" value="{{ old('name') }}" class="ks-form-input" placeholder="e.g. Main Business WhatsApp" required>
    </div>
    <div>
     <label class="ks-label">Provider</label>
     <select name="provider" class="ks-form-input" required>
      <option value="meta_cloud" @selected(old('provider')==='meta_cloud')>Meta Cloud API</option>
      <option value="evolution" @selected(old('provider')==='evolution')>Evolution API</option>
     </select>
    </div>
   </div>

   <div>
    <label class="ks-label">Phone Number ID</label>
    <input type="text" name="phone_number_id" value="{{ old('phone_number_id') }}" class="ks-form-input" placeholder="Meta: Phone Number ID from dashboard / Evolution: phone number" required>
    <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Meta Cloud: found in WhatsApp > Getting Started > Phone Numbers. Evolution: the WhatsApp number.</p>
   </div>

   <div>
    <label class="ks-label">API URL</label>
    <input type="url" name="api_url" value="{{ old('api_url') }}" class="ks-form-input" placeholder="https://graph.facebook.com/v19.0 (Meta default) or Evolution API URL">
    <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Leave empty for Meta default (https://graph.facebook.com/v19.0). For Evolution, enter your server URL.</p>
   </div>

   <div>
    <label class="ks-label">API Token</label>
    <input type="password" name="api_token" value="{{ old('api_token') }}" class="ks-form-input" placeholder="Meta: Permanent Access Token / Evolution: API Key" {{ request()->routeIs('erp.admin.whatsapp.create') ? 'required' : '' }}>
    <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Meta: Generate from System Users > Tokens. Evolution: found in API settings.</p>
   </div>

   <div>
    <label class="ks-label">Verify Token</label>
    <input type="text" name="verify_token" value="{{ old('verify_token') }}" class="ks-form-input" placeholder="consultiser-verify (or your custom token)">
    <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Used for webhook verification. Must match what you set in Meta/Evolution dashboard.</p>
   </div>

   @error('name')<div class="ks-alert ks-alert-error" style="margin-top:8px;">{{ $message }}</div>@enderror
   @error('phone_number_id')<div class="ks-alert ks-alert-error" style="margin-top:8px;">{{ $message }}</div>@enderror

   <div class="flex gap-3">
    <button type="submit" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:14px;"></i> Create Account</button>
    <a href="{{ route('erp.admin.whatsapp.index') }}" class="ks-btn ks-btn-light">Cancel</a>
   </div>
  </form>
 </div>
</div>
@endsection
