@extends('erp.layouts.app')
@section('title', 'Email Templates')
@section('content')
<div class="ks-page-header">
 <div><h1 class="ks-page-title">Email Templates</h1></div>
 <div class="ks-page-actions">
  <a href="{{ route('erp.email.logs') }}" class="ks-btn ks-btn-light ks-btn-sm"><i class="ti ti-history" style="font-size:14px;"></i> Logs</a>
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body">
  <h3 class="ks-card-title">New Template</h3>
   @can('email.templates')
   <form method="POST" action="{{ route('erp.email.templates.store') }}" class="ks-filter-bar" style="flex-direction:column;gap:12px;">
    @csrf
    <div class="ks-form-row">
     <input type="text" name="slug" placeholder="Slug (e.g. QUOTATION_SENT)" required class="ks-form-input">
     <input type="text" name="name" placeholder="Template name" required class="ks-form-input">
     <input type="text" name="subject" placeholder="Subject" required class="ks-form-input">
    </div>
    <textarea name="body" placeholder="Body — use @{{variable}} placeholders" rows="4" required class="ks-form-input"></textarea>
    <div><button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Create Template</button></div>
   </form>
   @endcan
 </div>
</div>
<div class="ks-card">
 <div class="ks-card-body-0">
  <div class="ks-table-wrap">
   <table class="ks-table ks-table-responsive">
    <thead>
     <tr>
      <th>Name</th>
      <th>Slug</th>
      <th>Subject</th>
      <th style="text-align:center;">Status</th>
      <th style="text-align:right;">Actions</th>
     </tr>
    </thead>
    <tbody>
     @forelse($templates as $t)
     <tr>
      <td class="ks-strong">{{ $t->name }}</td>
      <td style="font-family:monospace;font-size:12px;color:var(--gray-400);">{{ $t->slug }}</td>
      <td style="font-size:13px;">{{ $t->subject }}</td>
      <td style="text-align:center;">{!! $t->is_active ? '<span class="ks-badge ks-badge-green">Active</span>' : '<span class="ks-badge ks-badge-gray">Inactive</span>' !!}</td>
      <td style="text-align:right;">
       <button class="ks-btn ks-btn-light ks-btn-sm ks-template-edit" data-id="{{ $t->id }}" data-subject="{{ addslashes($t->subject) }}" data-body="{{ addslashes($t->body) }}" data-active="{{ $t->is_active ? '1' : '0' }}"><i class="ti ti-edit" style="font-size:14px;"></i> Edit</button>
      </td>
     </tr>
     @empty
     <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:48px;">No templates yet.</td></tr>
     @endforelse
    </tbody>
   </table>
  </div>
 </div>
 <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $templates->links() }}</div>
</div>

{{-- Edit Template Modal --}}
@can('email.templates')
<div id="templateModal" style="display:none;position:fixed;inset:0;z-index:1100;display:none;align-items:center;justify-content:center;padding:16px;">
 <div style="position:absolute;inset:0;background:rgba(15,15,30,0.5);" onclick="closeTemplateModal()"></div>
 <div class="ks-modal-panel" style="position:relative;max-width:600px;width:100%;max-height:calc(100vh - 48px);display:flex;flex-direction:column;">
  <div class="ks-modal-header">
   <h3 class="ks-card-title" style="margin:0;">Edit Template</h3>
   <button onclick="closeTemplateModal()" class="text-gray-400 hover:text-gray-600"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
  </div>
  <div class="ks-modal-body" style="flex:1;overflow-y:auto;">
   <form method="POST" id="templateEditForm" style="display:flex;flex-direction:column;gap:12px;">
    @csrf @method('PATCH')
    <div><label class="ks-form-label">Subject</label><input type="text" name="subject" id="tplSubject" class="ks-form-input"></div>
    <div><label class="ks-form-label">Body</label><textarea name="body" id="tplBody" rows="6" class="ks-form-input"></textarea></div>
    <div style="display:flex;align-items:center;justify-content:space-between;">
     <label style="display:flex;align-items:center;font-size:13px;color:var(--gray-600);cursor:pointer;"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" id="tplActive" value="1" style="margin-right:6px;"> Active</label>
     <button type="submit" class="ks-btn ks-btn-primary">Save Changes</button>
    </div>
   </form>
  </div>
 </div>
</div>
@endcan
@endsection

@push('scripts')
<script>
function closeTemplateModal() { document.getElementById('templateModal').style.display = 'none'; }
document.querySelectorAll('.ks-template-edit').forEach(btn => {
 btn.addEventListener('click', function() {
  var id = this.dataset.id;
  document.getElementById('templateEditForm').action = '/erp/email/templates/' + id;
  document.getElementById('tplSubject').value = this.dataset.subject;
  document.getElementById('tplBody').value = this.dataset.body;
  document.getElementById('tplActive').checked = this.dataset.active === '1';
  document.getElementById('templateModal').style.display = 'flex';
 });
});
</script>
@endpush
