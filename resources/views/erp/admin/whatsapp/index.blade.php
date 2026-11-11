@extends('erp.layouts.app')
@section('title', 'WhatsApp Accounts')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">WhatsApp Accounts</h1><p class="ks-page-sub">Manage Meta Cloud API & Evolution API integrations.</p></div>
 <div class="ks-page-actions">
  <a href="{{ route('erp.admin.whatsapp.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> Add Account</a>
 </div>
</div>

<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead>
     <tr>
      <th>Name</th>
      <th>Provider</th>
      <th>Phone Number ID</th>
      <th>Webhook URL</th>
      <th style="text-align:center;">Status</th>
      <th style="text-align:right;">Actions</th>
     </tr>
    </thead>
    <tbody>
     @forelse($accounts as $a)
     <tr>
      <td class="ks-strong">{{ $a->name }}</td>
      <td><span class="ks-badge ks-badge-{{ $a->provider === 'meta_cloud' ? 'blue' : 'green' }}">{{ $a->provider === 'meta_cloud' ? 'Meta Cloud API' : 'Evolution API' }}</span></td>
      <td style="font-family:monospace;font-size:12px;color:var(--gray-400);">{{ $a->phone_number_id }}</td>
      <td style="font-family:monospace;font-size:11px;color:var(--gray-400);word-break:break-all;">{{ url('/erp/whatsapp/webhook/'.$a->phone_number_id) }}</td>
      <td style="text-align:center;">{!! $a->is_active ? '<span class="ks-badge ks-badge-green">Active</span>' : '<span class="ks-badge ks-badge-gray">Inactive</span>' !!}</td>
      <td style="text-align:right;">
       <a href="{{ route('erp.admin.whatsapp.edit', $a) }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-edit" style="font-size:14px;"></i> Edit</a>
       <form method="POST" action="{{ route('erp.admin.whatsapp.destroy', $a) }}" style="display:inline;" onsubmit="return confirm('Delete this WhatsApp account?')">
        @csrf @method('DELETE')
        <button class="ks-btn ks-btn-light ks-btn-sm" style="color:var(--red-500);"><i class="ti ti-trash" style="font-size:14px;"></i></button>
       </form>
      </td>
     </tr>
     @empty
     <tr><td colspan="6" style="text-align:center;color:var(--gray-400);padding:48px;">No WhatsApp accounts configured. Add one to start receiving leads.</td></tr>
     @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $accounts->links() }}</div>
</div>

<div class="ks-alert ks-alert-info" style="display:flex;align-items:center;gap:10px;margin-top:16px;">
 <i class="ti ti-info-circle" style="font-size:20px;"></i>
 <div><b>Webhook Setup:</b> Set the webhook URL shown above in your Meta or Evolution API dashboard. Messages from configured numbers will auto-create leads and clients.</div>
</div>
@endsection
