@extends('erp.layouts.app')
@section('title', 'Edit WhatsApp Account')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Edit: {{ $account->name }}</h1></div>
 <div class="ks-page-actions">
  <a href="{{ route('erp.admin.whatsapp.index') }}" class="ks-btn ks-btn-light"><i class="ti ti-arrow-left" style="font-size:14px;"></i> Back</a>
 </div>
</div>

<div class="ks-card" style="max-width:720px;">
 <div class="ks-card-body" style="padding:28px 32px;">
  <form method="POST" action="{{ route('erp.admin.whatsapp.update', $account) }}" class="space-y-5">
   @csrf @method('PATCH')
   <div class="grid grid-cols-2 gap-4">
    <div>
     <label class="ks-label">Account Name</label>
     <input type="text" name="name" value="{{ old('name', $account->name) }}" class="ks-form-input" required>
    </div>
    <div>
     <label class="ks-label">Provider</label>
     <select name="provider" class="ks-form-input" required>
      <option value="meta_cloud" @selected(old('provider', $account->provider)==='meta_cloud')>Meta Cloud API</option>
      <option value="evolution" @selected(old('provider', $account->provider)==='evolution')>Evolution API</option>
     </select>
    </div>
   </div>

   <div>
    <label class="ks-label">Phone Number ID</label>
    <input type="text" name="phone_number_id" value="{{ old('phone_number_id', $account->phone_number_id) }}" class="ks-form-input" required>
   </div>

   <div>
    <label class="ks-label">API URL</label>
    <input type="url" name="api_url" value="{{ old('api_url', $account->api_url) }}" class="ks-form-input">
   </div>

   <div>
    <label class="ks-label">API Token</label>
    <input type="password" name="api_token" value="" class="ks-form-input" placeholder="Leave empty to keep current token">
    <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Current token: {{ substr($account->api_token, 0, 10) }}... (encrypted)</p>
   </div>

   <div>
    <label class="ks-label">Verify Token</label>
    <input type="text" name="verify_token" value="" class="ks-form-input" placeholder="Leave empty to keep current token">
   </div>

   <div style="display:flex;align-items:center;gap:8px;">
    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $account->is_active) ? 'checked' : '' }} style="width:16px;height:16px;">
    <label class="ks-label" style="margin:0;">Active</label>
   </div>

   @error('name')<div class="ks-alert ks-alert-error" style="margin-top:8px;">{{ $message }}</div>@enderror

   <div class="flex gap-3">
    <button type="submit" class="ks-btn ks-btn-primary"><i class="ti ti-check" style="font-size:14px;"></i> Update Account</button>
    <a href="{{ route('erp.admin.whatsapp.index') }}" class="ks-btn ks-btn-light">Cancel</a>
   </div>
  </form>
 </div>
</div>
@endsection
