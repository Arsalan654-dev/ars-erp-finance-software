@extends('erp.layouts.app') @section('title', $client->business_name)
@section('content')
<div class="ks-page-header">
 <div style="min-width:0;flex:1;">
 <h1 class="ks-page-title">{{ $client->business_name }}</h1>
 <p class="ks-page-sub">@if($client->client_number)<span class="ks-badge ks-badge-gray" style="margin-right:8px;">{{ $client->client_number }}</span>@endif{{ $client->email }} · {{ $client->phone }} · {{ $client->country }}</p>
 </div>
 <div class="ks-page-actions">
 {!! erp_status_badge($client->status) !!}
 @can('client.update')<a href="{{ route('erp.clients.edit', $client) }}" class="ks-btn ks-btn-primary ks-btn-sm">Edit</a>@endcan
 @if($client->phone)
 <button type="button" x-data="{ waOpen: false, waMsg: '' }" @click="waOpen = true" class="ks-btn ks-btn-sm" style="background:#25D366;color:#fff;border-color:#25D366;">
  WhatsApp
 </button>
 <div x-cloak x-show="waOpen" class="ks-modal-backdrop" @keydown.escape.window="waOpen = false">
  <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="waOpen = false"></div>
  <div class="ks-modal-panel" style="max-width:420px;">
   <div class="ks-modal-header">
    <h3 style="font-size:16px;font-weight:700;color:var(--gray-900);">Send WhatsApp</h3>
    <button @click="waOpen = false" class="ks-icon-btn"><i class="ti ti-x" style="font-size:16px;"></i></button>
   </div>
   <div class="ks-modal-body">
    <div class="flex items-center gap-2 mb-4 text-xs text-gray-500 bg-gray-50 rounded-lg px-3 py-2">
     <span class="font-semibold text-gray-700">To:</span> {{ $client->phone }}
    </div>
    <form method="POST" action="{{ route('erp.clients.whatsapp.send', $client) }}">
     @csrf
     <textarea x-model="waMsg" name="message" rows="4" required placeholder="Type your message..." class="ks-form-input" style="resize:vertical;min-height:100px;"></textarea>
     <div class="ks-modal-footer" style="padding:14px 0 0;border-top:1px solid var(--gray-100);margin-top:14px;">
      <button type="submit" class="ks-btn ks-btn-primary" style="width:100%;background:#25D366;border-color:#25D366;">Send Message</button>
     </div>
    </form>
   </div>
  </div>
 </div>
 @endif
 </div>
</div>

{{-- Sensitive identifiers --}}
<div class="ks-card">
 <div class="ks-card-header">
  <h3 class="ks-card-title">Tax Identifiers</h3>
  @if(!\App\Services\SensitiveDataService::canViewSensitive())
  <span class="ks-badge ks-badge-gray">Masked — admin only</span>
  @endif
 </div>
 <div class="ks-card-body">
  <div class="ks-form-row">
   <div><p class="ks-form-label" style="margin-bottom:2px;">EIN</p><p class="font-medium">{{ erp_mask($client->ein) ?? '—' }}</p></div>
   <div><p class="ks-form-label" style="margin-bottom:2px;">ITIN</p><p class="font-medium">{{ erp_mask($client->itin) ?? '—' }}</p></div>
  </div>
  <div class="ks-form-row">
   <div><p class="ks-form-label" style="margin-bottom:2px;">UTR</p><p class="font-medium">{{ erp_mask($client->utr) ?? '—' }}</p></div>
   <div><p class="ks-form-label" style="margin-bottom:2px;">SSN</p><p class="font-medium">{{ erp_mask($client->ssn) ?? '—' }}</p></div>
  </div>
 </div>
</div>

{{-- Tabs --}}
<div x-data="{ tab: '{{ in_array(request('tab'), ['contacts','services','quotations','invoices','payments','tasks','documents','compliance','notes','history']) ? request('tab') : 'overview' }}' }">
 <div style="display:flex;gap:2px;border-bottom:1px solid var(--gray-100);margin-bottom:22px;overflow-x:auto;-webkit-overflow-scrolling:touch;scrollbar-width:none;" class="ks-tabs">
 @php $tabs = [['overview','Overview'],['contacts','Contacts'],['services','Services'],['quotations','Quotations'],['invoices','Invoices'],['payments','Payments'],['tasks','Tasks'],['documents','Documents'],['compliance','Compliance'],['notes','Notes'],['history','History']]; @endphp
 @foreach($tabs as [$key, $label])
 <button x-on:click="tab = '{{ $key }}'" class="ks-tab" style="flex:0 0 auto;padding:10px 16px;font-size:13px;font-weight:500;border:none;background:none;cursor:pointer;white-space:nowrap;border-bottom:2px solid transparent;color:var(--gray-400);transition:all 0.15s;" :class="tab === '{{ $key }}' ? 'role-text' : 'hover:text-gray-700'" :style="tab === '{{ $key }}' ? 'border-bottom-color:var(--role-accent);color:var(--role-accent);' : ''">{{ $label }}</button>
 @endforeach
 </div>

 {{-- Overview --}}
 <div x-show="tab === 'overview'">
 <div class="ks-grid-2" style="grid-template-columns:2fr 1fr;">
  <div class="ks-card">
   <div class="ks-card-header"><h3 class="ks-card-title">Client Details</h3></div>
   <div class="ks-card-body">
    <div class="ks-form-row">
     <div><p class="ks-form-label" style="margin-bottom:2px;">Legal Name</p><p>{{ $client->legal_name ?? '—' }}</p></div>
     <div><p class="ks-form-label" style="margin-bottom:2px;">Tax Type</p><p>{{ $client->tax_type ?? '—' }}</p></div>
    </div>
    <div class="ks-form-row">
     <div><p class="ks-form-label" style="margin-bottom:2px;">Address</p><p>{{ $client->address ?? '—' }}</p></div>
     <div><p class="ks-form-label" style="margin-bottom:2px;">Created</p><p>{{ $client->created_at->format('M d, Y') }}</p></div>
    </div>
    <div style="margin-top:14px;padding-top:14px;border-top:1px solid var(--gray-100);">
     <p class="text-sm" style="color:var(--gray-600);">{{ $client->notes ?? 'No notes.' }}</p>
    </div>
   </div>
  </div>
  <div class="ks-card">
   <div class="ks-card-header"><h3 class="ks-card-title">Counts</h3></div>
   <div class="ks-card-body">
    <div class="space-y-2 text-sm">
     <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--gray-500);">Quotations</span><span class="font-semibold">{{ $quotations->total() }}</span></div>
     <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--gray-500);">Invoices</span><span class="font-semibold">{{ $invoices->total() }}</span></div>
     <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--gray-500);">Payments</span><span class="font-semibold">{{ $payments->total() }}</span></div>
     <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--gray-500);">Tasks</span><span class="font-semibold">{{ $tasks->total() }}</span></div>
     <div style="display:flex;justify-content:space-between;font-size:13px;"><span style="color:var(--gray-500);">Documents</span><span class="font-semibold">{{ $documents->total() }}</span></div>
    </div>
   </div>
  </div>
 </div>
 </div>

 {{-- Contacts --}}
 <div x-show="tab === 'contacts'">
 <div class="ks-card">
  <div class="ks-card-body">
   @can('client.update')
    <form method="POST" action="{{ route('erp.clients.contacts.store', $client) }}" class="ks-filter-bar" style="margin-bottom:20px;">
     @csrf
     <input name="first_name" placeholder="First name" required class="ks-form-input ks-filter-search">
     <input name="last_name" placeholder="Last name" required class="ks-form-input ks-filter-search">
     <input name="email" placeholder="Email" class="ks-form-input ks-filter-search">
     <input name="phone" placeholder="Phone" class="ks-form-input ks-filter-search">
     <input name="position" placeholder="Position" class="ks-form-input ks-filter-search">
     <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Add</button>
    </form>
    @endcan
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>Name</th><th>Email</th><th>Phone</th><th>Position</th><th style="text-align:right;">Actions</th></tr></thead>
     <tbody>
     @forelse($contacts as $contact)
     <tr>
      <td data-label="Name"><span class="font-medium">{{ $contact->first_name }} {{ $contact->last_name }}</span> @if($contact->is_primary)<span class="ks-badge ks-badge-indigo" style="margin-left:6px;">PRIMARY</span>@endif</td>
      <td data-label="Email">{{ $contact->email }}</td>
      <td data-label="Phone">{{ $contact->phone }}</td>
      <td data-label="Position">{{ $contact->position }}</td>
       <td data-label="Actions" style="text-align:right;">
        @can('client.delete')
        <form method="POST" action="{{ route('erp.clients.contacts.destroy', [$client, $contact]) }}" onsubmit="return confirm('Remove contact?')" style="display:inline;">@csrf @method('DELETE')<button class="ks-btn ks-btn-xs" style="color:#dc2626;">Remove</button></form>
        @endcan
       </td>
     </tr>
     @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No contacts.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
   <div style="padding-top:14px;">{{ $contacts->appends(['tab' => 'contacts'])->links() }}</div>
  </div>
 </div>
 </div>

 {{-- Services --}}
 <div x-show="tab === 'services'">
 <div class="ks-card">
  <div class="ks-card-body">
   @can('client.update')
    <form method="POST" action="{{ route('erp.clients.services.store', $client) }}" class="ks-filter-bar" style="margin-bottom:20px;">
     @csrf
     <select name="service_name" required class="ks-form-input" style="max-width:160px;"><option>US Tax</option><option>UK Tax</option><option>Legal</option><option>Consulting</option></select>
     <input name="start_date" type="date" class="ks-form-input" style="max-width:160px;">
     <input name="end_date" type="date" class="ks-form-input" style="max-width:160px;">
     <input name="notes" placeholder="Notes" class="ks-form-input ks-filter-search">
     <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Add</button>
    </form>
    @endcan
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>Service</th><th>Status</th><th>Start</th><th>End</th><th style="text-align:right;">Actions</th></tr></thead>
     <tbody>
     @forelse($services as $svc)
     <tr>
      <td data-label="Service"><span class="font-medium">{{ $svc->service_name }}</span></td>
      <td data-label="Status">{!! erp_status_badge($svc->status) !!}</td>
      <td data-label="Start">{{ $svc->start_date?->format('Y-m-d') }}</td>
      <td data-label="End">{{ $svc->end_date?->format('Y-m-d') }}</td>
       <td data-label="Actions" style="text-align:right;">
        @can('client.delete')
        <form method="POST" action="{{ route('erp.clients.services.destroy', [$client, $svc]) }}" onsubmit="return confirm('Remove service?')" style="display:inline;">@csrf @method('DELETE')<button class="ks-btn ks-btn-xs" style="color:#dc2626;">Remove</button></form>
        @endcan
       </td>
     </tr>
     @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No services.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
   <div style="padding-top:14px;">{{ $services->appends(['tab' => 'services'])->links() }}</div>
  </div>
 </div>
 </div>

 {{-- Quotations --}}
 <div x-show="tab === 'quotations'">
 <div class="ks-card">
  <div class="ks-card-header">
   <h3 class="ks-card-title">Quotations</h3>
    @can('quotation.create')<a href="{{ route('erp.quotations.create') }}?client={{ $client->id }}" class="ks-btn ks-btn-light ks-btn-sm">+ New</a>@endcan
  </div>
  <div class="ks-card-body-0">
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>#</th><th>Currency</th><th style="text-align:right;">Total</th><th style="text-align:center;">Status</th><th>Valid Until</th></tr></thead>
     <tbody>
     @forelse($quotations as $q)
     <tr>
      <td data-label="#"><a href="{{ route('erp.quotations.show', $q) }}" class="role-text" style="text-decoration:none;font-weight:600;">{{ $q->quotation_number }}</a></td>
      <td data-label="Currency">{{ $q->currency }}</td>
      <td data-label="Total" style="text-align:right;font-weight:600;">{{ erp_money($q->total, $q->currency) }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($q->status) !!}</td>
      <td data-label="Valid Until">{{ $q->valid_until?->format('Y-m-d') }}</td>
     </tr>
     @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No quotations.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
  </div>
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $quotations->appends(['tab' => 'quotations'])->links() }}</div>
 </div>
 </div>

 {{-- Invoices --}}
 <div x-show="tab === 'invoices'">
 <div class="ks-card">
  <div class="ks-card-header">
   <h3 class="ks-card-title">Invoices</h3>
    @can('invoice.create')<a href="{{ route('erp.invoices.create') }}?client={{ $client->id }}" class="ks-btn ks-btn-light ks-btn-sm">+ New</a>@endcan
  </div>
  <div class="ks-card-body-0">
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>#</th><th>Currency</th><th style="text-align:right;">Total</th><th style="text-align:right;">Balance</th><th style="text-align:center;">Status</th></tr></thead>
     <tbody>
     @forelse($invoices as $inv)
     <tr>
      <td data-label="#"><a href="{{ route('erp.invoices.show', $inv) }}" class="role-text" style="text-decoration:none;font-weight:600;">{{ $inv->invoice_number }}</a></td>
      <td data-label="Currency">{{ $inv->currency }}</td>
      <td data-label="Total" style="text-align:right;font-weight:600;">{{ erp_money($inv->total, $inv->currency) }}</td>
      <td data-label="Balance" style="text-align:right;">{{ erp_money($inv->balance, $inv->currency) }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($inv->status) !!}</td>
     </tr>
     @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No invoices.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
  </div>
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $invoices->appends(['tab' => 'invoices'])->links() }}</div>
 </div>
 </div>

 {{-- Payments --}}
 <div x-show="tab === 'payments'">
 <div class="ks-card">
  <div class="ks-card-header"><h3 class="ks-card-title">Payments</h3></div>
  <div class="ks-card-body-0">
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>#</th><th>Currency</th><th style="text-align:right;">Amount</th><th>Date</th><th style="text-align:center;">Status</th></tr></thead>
     <tbody>
     @forelse($payments as $p)
     <tr>
      <td data-label="#"><a href="{{ route('erp.finance.payments.show', $p) }}" class="role-text" style="text-decoration:none;font-weight:600;">{{ $p->payment_number }}</a></td>
      <td data-label="Currency">{{ $p->currency }}</td>
      <td data-label="Amount" style="text-align:right;font-weight:600;">{{ erp_money($p->amount, $p->currency) }}</td>
      <td data-label="Date">{{ $p->payment_date?->format('Y-m-d') }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($p->status) !!}</td>
     </tr>
     @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No payments.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
  </div>
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $payments->appends(['tab' => 'payments'])->links() }}</div>
 </div>
 </div>

 {{-- Tasks --}}
 <div x-show="tab === 'tasks'">
 <div class="ks-card">
  <div class="ks-card-header">
   <h3 class="ks-card-title">Tasks</h3>
    @can('task.create')<a href="{{ route('erp.tasks.create') }}?client={{ $client->id }}" class="ks-btn ks-btn-light ks-btn-sm">+ New</a>@endcan
  </div>
  <div class="ks-card-body-0">
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>#</th><th>Title</th><th>Department</th><th>Due</th><th style="text-align:center;">Status</th></tr></thead>
     <tbody>
     @forelse($tasks as $t)
     <tr>
      <td data-label="#"><a href="{{ route('erp.tasks.show', $t) }}" class="role-text" style="text-decoration:none;font-weight:600;">{{ $t->task_number }}</a></td>
      <td data-label="Title"><span class="font-medium">{{ $t->title }}</span></td>
      <td data-label="Department">{{ $t->department }}</td>
      <td data-label="Due">{{ $t->due_date?->format('Y-m-d') }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($t->status) !!}</td>
     </tr>
     @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No tasks.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
  </div>
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $tasks->appends(['tab' => 'tasks'])->links() }}</div>
 </div>
 </div>

 {{-- Documents --}}
 <div x-show="tab === 'documents'">
 <div class="ks-card">
  <div class="ks-card-header"><h3 class="ks-card-title">Documents (private vault)</h3></div>
  <div class="ks-card-body-0">
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>Filename</th><th>Category</th><th>Size</th><th>Uploaded</th><th style="text-align:right;">Access</th></tr></thead>
     <tbody>
     @forelse($documents as $doc)
     <tr>
      <td data-label="Filename"><span class="font-medium">{{ $doc->original_filename }}</span></td>
      <td data-label="Category">{{ $doc->category }}</td>
      <td data-label="Size">{{ round($doc->size / 1024, 1) }} KB</td>
      <td data-label="Uploaded">{{ $doc->created_at->format('Y-m-d') }}</td>
      <td data-label="Access" style="text-align:right;"><a href="{{ route('erp.documents.show', $doc) }}" class="role-text ks-btn ks-btn-xs">View</a></td>
     </tr>
     @empty <tr><td colspan="5" style="text-align:center;color:var(--gray-400);padding:40px;">No documents.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
  </div>
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $documents->appends(['tab' => 'documents'])->links() }}</div>
 </div>
 </div>

 {{-- Compliance --}}
 <div x-show="tab === 'compliance'">
 <div class="ks-card">
  <div class="ks-card-header"><h3 class="ks-card-title">Compliance Records</h3></div>
  <div class="ks-card-body-0">
   <div class="ks-table-wrap">
    <table class="ks-table ks-table-responsive">
     <thead><tr><th>Type</th><th>Reference</th><th>Expiry</th><th style="text-align:center;">Status</th></tr></thead>
     <tbody>
     @forelse($complianceRecords as $rec)
     <tr>
      <td data-label="Type"><a href="{{ route('erp.compliance.show', $rec) }}" class="role-text" style="text-decoration:none;font-weight:600;">{{ $rec->document_type }}</a></td>
      <td data-label="Reference">{{ $rec->reference_number }}</td>
      <td data-label="Expiry">{{ $rec->expiry_date?->format('Y-m-d') }}</td>
      <td data-label="Status" style="text-align:center;">{!! erp_status_badge($rec->status) !!}</td>
     </tr>
     @empty <tr><td colspan="4" style="text-align:center;color:var(--gray-400);padding:40px;">No compliance records.</td></tr> @endforelse
     </tbody>
    </table>
   </div>
  </div>
  <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">{{ $complianceRecords->appends(['tab' => 'compliance'])->links() }}</div>
 </div>
 </div>

 {{-- Notes --}}
 <div x-show="tab === 'notes'">
 <div class="ks-card">
  <div class="ks-card-body">
   @can('client.update')
    <form method="POST" action="{{ route('erp.clients.notes.store', $client) }}" class="ks-filter-bar" style="margin-bottom:20px;">
     @csrf
     <input name="content" placeholder="Add a note..." required class="ks-form-input ks-filter-search">
     <select name="type" class="ks-form-input" style="max-width:130px;"><option value="general">General</option><option value="call">Call</option><option value="email">Email</option><option value="meeting">Meeting</option></select>
     <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Add</button>
    </form>
    @endcan
   <div class="space-y-3">
    @forelse($client->clientNotes->sortByDesc('created_at') as $note)
    <div style="padding:14px;border-radius:10px;background:var(--gray-50);border:1px solid var(--gray-100);">
     <p style="font-size:13px;color:var(--gray-700);">{{ $note->content }}</p>
     <p style="font-size:11px;color:var(--gray-400);margin-top:6px;">{{ $note->user->name ?? 'Unknown' }} · {{ $note->created_at->diffForHumans() }} · {{ ucfirst($note->type) }}</p>
    </div>
    @empty <p style="text-align:center;color:var(--gray-400);padding:40px 0;font-size:13px;">No notes.</p> @endforelse
   </div>
  </div>
 </div>
 </div>

 {{-- History / Audit --}}
 <div x-show="tab === 'history'">
 <div class="ks-card">
  <div class="ks-card-header"><h3 class="ks-card-title">Client Activity Log</h3></div>
  <div class="ks-card-body">
   <div class="space-y-2">
    @forelse($client->history as $h)
    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 0;border-bottom:1px solid var(--gray-100);font-size:13px;gap:12px;flex-wrap:wrap;">
     <span style="color:var(--gray-600);">{{ $h->action }}</span>
     <span style="font-size:11px;color:var(--gray-400);white-space:nowrap;">{{ $h->user->name ?? 'System' }} · {{ $h->created_at }}</span>
    </div>
    @empty <p style="text-align:center;color:var(--gray-400);padding:40px 0;font-size:13px;">No history recorded yet.</p> @endforelse
   </div>
  </div>
 </div>
 </div>
</div>
@endsection

@push('styles')
<style>
@media (max-width: 991px) {
    .ks-grid-2[style*="grid-template-columns:2fr 1fr"] {
        grid-template-columns: 1fr !important;
    }
}
@media (max-width: 575px) {
    .ks-filter-bar {
        flex-direction: column;
    }
    .ks-filter-bar .ks-form-input {
        width: 100%;
    }
    .ks-filter-bar .ks-btn {
        width: 100%;
    }
}
</style>
@endpush
