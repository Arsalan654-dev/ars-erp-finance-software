@extends('erp.layouts.app')
@section('title', 'Lead: ' . ($lead->business_name ?? $lead->contact_name ?? 'N/A'))
@section('content')

<div x-data="{ convertOpen: false, lostOpen: false, ptype: '{{ old('payment_type', 'full') }}' }">
<div class="ks-page-header">
    <div>
        <a href="{{ route('erp.leads.index') }}" style="font-size:12px;color:var(--gray-400);text-decoration:none;">← Back to Leads</a>
        <h1 class="ks-page-title">{{ $lead->business_name ?? $lead->contact_name ?? 'N/A' }}</h1>
        <div style="display:flex;align-items:center;gap:8px;margin-top:4px;">
            {!! erp_status_badge($lead->status) !!}
            @if($lead->is_duplicate)
                <span class="ks-badge ks-badge-red" title="Duplicate lead — matched against existing data">Duplicate</span>
                @if($lead->duplicateOf)
                    <a href="{{ route('erp.leads.show', $lead->duplicateOf) }}" style="font-size:12px;color:var(--gray-400);text-decoration:underline;">Original lead</a>
                @endif
            @endif
            <span style="font-size:13px;color:var(--gray-400);">{{ $lead->source }} · {{ $lead->department }}</span>
        </div>
    </div>
    <div class="ks-page-actions">
        @if(!in_array($lead->status, ['converted', 'lost']))
        @can('lead.update')
        <a href="{{ route('erp.leads.edit', $lead) }}" class="ks-btn ks-btn-light"><i class="ti ti-edit" style="font-size:14px;"></i> Edit</a>
        @endcan
        @endif
        @if(in_array($lead->status, ['qualified', 'quotation']))
        @can('lead.convert')
        <button type="button" class="ks-btn ks-btn-primary" @click="convertOpen = true"><i class="ti ti-check" style="font-size:14px;"></i> Convert to Client</button>
        @endcan
        @endif
    </div>
</div>

{{-- Convert to Client modal (with payment details) --}}
@if(in_array($lead->status, ['qualified', 'quotation']))
@can('lead.convert')
<div x-cloak x-show="convertOpen" x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     class="ks-modal-backdrop" @keydown.escape.window="convertOpen = false" @click.self="convertOpen = false" style="z-index:1100;">
    <div class="ks-modal-panel" style="max-width:560px;width:calc(100vw - 32px);max-height:calc(100vh - 48px);display:flex;flex-direction:column;">
        <div class="ks-modal-header">
            <div>
                <h3 class="font-bold text-gray-900">Convert to Client</h3>
                <p class="text-xs text-gray-400">Payment details are required to convert this lead.</p>
            </div>
            <button type="button" @click="convertOpen = false" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="ks-modal-body" style="flex:1;overflow-y:auto;">
            <form method="POST" action="{{ route('erp.leads.convert', $lead) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="ks-form-label">Payment Type *</label>
                        <select name="payment_type" x-model="ptype" class="ks-form-input" required>
                            <option value="full">Full Payment</option>
                            <option value="partial">Partial Payment</option>
                            <option value="special">Special Approval</option>
                        </select>
                    </div>
                    <div>
                        <label class="ks-form-label">Currency *</label>
                        <select name="payment_currency" class="ks-form-input" required>
                            @foreach(['USD','GBP','EUR','PKR'] as $cur)
                            <option value="{{ $cur }}" @selected(old('payment_currency', 'USD') === $cur)>{{ $cur }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="ks-form-label">Amount <span x-show="ptype !== 'special'">*</span></label>
                        <input type="number" step="0.01" min="0" name="payment_amount" value="{{ old('payment_amount') }}" class="ks-form-input" :required="ptype !== 'special'">
                    </div>
                    <div>
                        <label class="ks-form-label">Payment Date</label>
                        <input type="date" name="payment_date" value="{{ old('payment_date', now()->toDateString()) }}" class="ks-form-input">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="ks-form-label">Method</label>
                        <select name="payment_method" class="ks-form-input">
                            @foreach(['bank','cash','card','cheque','online'] as $m)
                            <option value="{{ $m }}" @selected(old('payment_method') === $m)>{{ ucfirst($m) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="ks-form-label">Reference / Txn ID</label>
                        <input type="text" name="payment_reference" value="{{ old('payment_reference') }}" class="ks-form-input" placeholder="Transaction ID">
                    </div>
                </div>

                <div>
                    <label class="ks-form-label">Payment Screenshot <span x-show="ptype !== 'special'">*</span> (JPG/PNG/PDF, max 5MB)</label>
                    <input type="file" name="payment_screenshot" accept=".jpg,.jpeg,.png,.pdf" class="ks-form-input" :required="ptype !== 'special'">
                </div>

                <div>
                    <label class="ks-form-label">Amount Visible on Screenshot <span x-show="ptype !== 'special'">*</span></label>
                    <input type="number" step="0.01" min="0" name="screenshot_amount" value="{{ old('screenshot_amount') }}" class="ks-form-input" :required="ptype !== 'special'"
                           placeholder="Type the amount you see on the screenshot">
                    <p class="text-xs" style="color:var(--gray-400);margin-top:4px;" x-show="ptype !== 'special'">
                        If this differs from the amount above, the payment is flagged as a mismatch and reported to the admin.
                    </p>
                </div>

                <div x-show="ptype === 'special'" x-cloak>
                    <label class="ks-form-label">Special Approval Note *</label>
                    <textarea name="special_approval_note" rows="2" class="ks-form-input" placeholder="Reason / approval reference for special payment type">{{ old('special_approval_note') }}</textarea>
                    <p class="text-xs" style="color:var(--gray-400);margin-top:4px;">Special approval converts without payment screenshot requirements — restricted to finance/super-admin.</p>
                </div>

                @if($errors->any())
                <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:10px 14px;font-size:13px;color:#b91c1c;">
                    <ul style="margin:0;padding-left:16px;">
                        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
                @endif

                <div style="display:flex;gap:8px;justify-content:flex-end;padding-top:6px;">
                    <button type="button" class="ks-btn ks-btn-light" @click="convertOpen = false">Cancel</button>
                    <button type="submit" class="ks-btn ks-btn-primary">Convert to Client</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endcan
@endif

<div class="ks-grid-3" style="grid-template-columns:2fr 1fr;gap:22px;">
    {{-- Left Column --}}
    <div class="space-y-4">
        {{-- Lead Details --}}
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Lead Details</p>
            </div>
            <div class="ks-card-body">
                <div class="grid grid-cols-2 gap-3" style="font-size:13px;">
                    <div><span style="color:var(--gray-400);">Business:</span> <span class="ks-strong">{{ $lead->business_name ?? '—' }}</span></div>
                    <div><span style="color:var(--gray-400);">Contact:</span> <span class="ks-strong">{{ $lead->contact_name ?? '—' }}</span></div>
                    <div><span style="color:var(--gray-400);">Campaign:</span> {{ $lead->campaign_name ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Email:</span> {{ $lead->email ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Phone:</span> {{ $lead->phone ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Assigned To:</span> {{ $lead->assignee?->name ?? 'Unassigned' }}</div>
                    <div><span style="color:var(--gray-400);">Created By:</span> {{ $lead->creator?->name ?? '—' }}</div>
                    <div><span style="color:var(--gray-400);">Created:</span> {{ $lead->created_at->format('d M Y H:i') }}</div>
                    @if($lead->converted_at)
                    <div><span style="color:var(--gray-400);">Converted:</span> {{ \Carbon\Carbon::parse($lead->converted_at)->format('d M Y H:i') }}</div>
                    @endif
                    @if($lead->client_id)
                    <div><span style="color:var(--gray-400);">Client:</span> <a href="{{ route('erp.clients.show', $lead->client_id) }}" style="color:var(--role-accent);text-decoration:none;">{{ $lead->client?->client_number ?? '#'.$lead->client_id }}</a></div>
                    @endif
                    @if($lead->lost_reason)
                    <div class="col-span-2"><span style="color:var(--gray-400);">Lost Reason:</span> <span style="color:var(--red-500);">{{ $lead->lost_reason }}</span></div>
                    @endif
                    @if($lead->lost_note)
                    <div class="col-span-2"><span style="color:var(--gray-400);">Lost Note:</span> {{ $lead->lost_note }}</div>
                    @endif
                </div>
                @if($lead->notes)
                <div style="margin-top:12px;padding:10px 14px;background:var(--gray-50);border-radius:8px;font-size:13px;color:var(--gray-600);">{{ $lead->notes }}</div>
                @endif
            </div>
        </div>

        {{-- Status Transition --}}
        @if($lead->status !== 'converted' && $lead->status !== 'lost')
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Move Pipeline</p>
            </div>
            <div class="ks-card-body">
                <div style="display:flex;gap:8px;flex-wrap:wrap;">
                    @php($transitions = ['new'=>['contacted','qualified','follow_up'],'contacted'=>['follow_up','qualified','lost'],'follow_up'=>['qualified','lost'],'qualified'=>['quotation','lost'],'quotation'=>['lost']])
                    @can('lead.update')
                    @foreach(($transitions[$lead->status] ?? []) as $next)
                        @if($next === 'lost')
                        <button type="button" class="ks-btn ks-btn-danger ks-btn-sm"
                                @click="lostOpen = true">→ Mark Lost</button>
                        @else
                        <form method="POST" action="{{ route('erp.leads.transition', $lead) }}">
                            @csrf
                            <input type="hidden" name="status" value="{{ $next }}">
                            <button class="ks-btn ks-btn-light ks-btn-sm">→ {{ ucfirst(str_replace('_',' ', $next)) }}</button>
                        </form>
                        @endif
                    @endforeach
                    @endcan
                </div>
                <p style="font-size:12px;color:var(--gray-400);margin:10px 0 0;">
                    Valid moves: {{ implode(', ', array_map(fn ($s) => ucfirst(str_replace('_',' ', $s)), $transitions[$lead->status] ?? [])) }}
                </p>
            </div>
        </div>

        {{-- Mark Lost modal (reason template + special note) --}}
        <div x-cloak x-show="lostOpen" x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="fixed inset-0 z-50 flex items-center justify-center p-4"
             style="background:rgba(15,23,42,0.55);" @keydown.escape.window="lostOpen = false">
            <div class="ks-card" style="max-width:520px;width:100%;margin:0;" @click.outside="lostOpen = false">
                <div class="ks-card-header">
                    <p class="ks-card-title" style="margin:0;">Mark Lead as Lost</p>
                    <button type="button" class="ks-btn ks-btn-light ks-btn-sm" @click="lostOpen = false"><i class="ti ti-x"></i></button>
                </div>
                <div class="ks-card-body">
                    <form method="POST" action="{{ route('erp.leads.transition', $lead) }}">
                        @csrf
                        <input type="hidden" name="status" value="lost">

                        <label class="ks-form-label">Lost Reason (template) *</label>
                        <select name="lost_reason" class="ks-form-input" required>
                            <option value="">Select a reason…</option>
                            @foreach(config('erp.lead_lost_reasons', []) as $reason)
                                <option value="{{ $reason }}" @selected(old('lost_reason') === $reason)>{{ $reason }}</option>
                            @endforeach
                        </select>
                        @error('lost_reason')<p style="color:var(--red-500);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror

                        <label class="ks-form-label" style="margin-top:14px;">Special Note (optional)</label>
                        <textarea name="lost_note" rows="3" maxlength="1000" class="ks-form-input"
                                  placeholder="Anything specific about why this lead was lost…">{{ old('lost_note') }}</textarea>
                        @error('lost_note')<p style="color:var(--red-500);font-size:12px;margin-top:4px;">{{ $message }}</p>@enderror

                        <div style="display:flex;gap:8px;justify-content:flex-end;margin-top:16px;">
                            <button type="button" class="ks-btn ks-btn-light" @click="lostOpen = false">Cancel</button>
                            <button type="submit" class="ks-btn ks-btn-danger">Mark Lost</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif

        {{-- Activity Log --}}
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Activities</p>
            </div>
            <div class="ks-card-body">
                @forelse($lead->activities as $act)
                <div style="border-bottom:1px solid var(--gray-100);padding:10px 0;">
                    <div style="display:flex;align-items:center;justify-content:space-between;font-size:12px;">
                        {!! erp_status_badge($act->type) !!}
                        <span style="color:var(--gray-400);">{{ $act->created_at->diffForHumans() }} by {{ $act->user?->name ?? '—' }}</span>
                    </div>
                    @if($act->subject)<div style="font-size:13px;font-weight:600;margin-top:4px;">{{ $act->subject }}</div>@endif
                    <div style="font-size:13px;color:var(--gray-500);margin-top:2px;">{{ $act->body }}</div>
                </div>
                @empty
                <p style="color:var(--gray-400);font-size:13px;">No activities yet.</p>
                @endforelse

                @can('lead.update')
                <form method="POST" action="{{ route('erp.leads.activity.store', $lead) }}" style="margin-top:16px;">
                    @csrf
                    <div style="display:flex;gap:8px;margin-bottom:8px;">
                        <select name="type" class="ks-form-input" style="width:130px;">
                            @foreach(['note','call','meeting','whatsapp','email'] as $t)
                                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                            @endforeach
                        </select>
                        <input type="text" name="subject" placeholder="Subject (optional)" class="ks-form-input" style="flex:1;">
                    </div>
                    <textarea name="body" rows="2" required placeholder="Log activity..." class="ks-form-input" style="margin-bottom:8px;"></textarea>
                    <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm">Log Activity</button>
                </form>
                @endcan
            </div>
        </div>
    </div>

    {{-- Right Column: Status History --}}
    <div class="space-y-4">
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">Status History</p>
        </div>
        <div class="ks-card-body">
            @forelse($lead->statusHistory as $sh)
            <div style="border-bottom:1px solid var(--gray-100);padding:10px 0;font-size:13px;">
                <div class="ks-strong">{{ $sh->from_status ?? '—' }} → {{ $sh->to_status }}</div>
                <div style="font-size:11px;color:var(--gray-400);margin-top:2px;">{{ $sh->created_at?->diffForHumans() ?? '—' }} by {{ $sh->changedBy?->name ?? '—' }}</div>
                @if($sh->reason)<div style="font-size:12px;color:var(--gray-400);margin-top:2px;">{{ $sh->reason }}</div>@endif
            </div>
            @empty
            <p style="color:var(--gray-400);font-size:13px;">No status changes yet.</p>
            @endforelse
        </div>
    </div>

    {{-- Payment Details (after conversion) --}}
    @if($lead->status === 'converted' && $lead->payment_type)
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">Payment Details</p>
        </div>
        <div class="ks-card-body" style="font-size:13px;">
            <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Type</span><span class="ks-strong">{{ ucfirst($lead->payment_type) }}</span></div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Amount</span><span class="ks-strong">{{ $lead->payment_amount !== null ? erp_money($lead->payment_amount, $lead->payment_currency ?? 'USD') : '—' }}</span></div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Method</span><span>{{ $lead->payment_method ?? '—' }}</span></div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Reference</span><span>{{ $lead->payment_reference ?? '—' }}</span></div>
            <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Date</span><span>{{ $lead->payment_date?->format('d M Y') ?? '—' }}</span></div>
            @if($lead->payment_screenshot_path)
            <div style="display:flex;justify-content:space-between;padding:6px 0;"><span style="color:var(--gray-500);">Screenshot</span><a href="{{ route('erp.leads.payment-screenshot', $lead) }}" class="role-text" target="_blank"><i class="ti ti-photo" style="font-size:13px;"></i> View</a></div>
            @endif
            @if($lead->payment_type === 'special' && $lead->payment_approval_note)
            <div style="margin-top:8px;padding:10px 14px;background:#fffbeb;border:1px solid #fde68a;border-radius:8px;color:#92400e;">
                <strong>Special Approval:</strong> {{ $lead->payment_approval_note }}<br>
                <span style="font-size:12px;">Approved by {{ $lead->approver?->name ?? '—' }}</span>
            </div>
            @endif

            @php($leadPayment = $lead->payments()->latest()->first())
            @if($leadPayment)
            <div style="margin-top:12px;padding-top:12px;border-top:1px solid var(--gray-100);">
                <div style="display:flex;justify-content:space-between;align-items:center;">
                    @if(auth()->user() && (auth()->user()->isSuperAdmin() || auth()->user()->hasRole('finance')))
                    <a href="{{ route('erp.finance.payments.show', $leadPayment) }}" class="role-text">{{ $leadPayment->payment_number }}</a>
                    @else
                    <span class="ks-strong">{{ $leadPayment->payment_number }}</span>
                    @endif
                    {!! erp_status_badge($leadPayment->status) !!}
                </div>
            </div>
            @endif
        </div>
    </div>
    @endif
    </div>
</div>
</div>
@endsection
