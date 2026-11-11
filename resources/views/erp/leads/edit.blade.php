@extends('erp.layouts.app')
@section('title', 'Edit Lead')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Edit Lead</h1>
        <p class="ks-page-sub">Update lead information.</p>
    </div>
    <div class="ks-page-actions">
        <a href="{{ route('erp.leads.show', $lead) }}" class="ks-btn ks-btn-light"><i class="ti ti-arrow-left" style="font-size:14px;"></i> Back</a>
    </div>
</div>

<div class="ks-card" style="max-width:720px;">
    <div class="ks-card-body" style="padding:28px 32px;">
        <form method="POST" action="{{ route('erp.leads.update', $lead) }}" class="space-y-5">
            @csrf @method('PATCH')
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="ks-label">Business Name</label>
                    <input type="text" name="business_name" value="{{ old('business_name', $lead->business_name) }}" class="ks-form-input">
                </div>
                <div>
                    <label class="ks-label">Contact Name</label>
                    <input type="text" name="contact_name" value="{{ old('contact_name', $lead->contact_name) }}" class="ks-form-input">
                </div>
                <div>
                    <label class="ks-label">Email</label>
                    <input type="email" name="email" value="{{ old('email', $lead->email) }}" class="ks-form-input">
                </div>
                <div>
                    <label class="ks-label">Phone</label>
                    <input type="text" name="phone" value="{{ old('phone', $lead->phone) }}" class="ks-form-input">
                </div>
                <div>
                    <label class="ks-label">Source</label>
                    <select name="source" class="ks-form-input">
                        @php($sourceOptions = collect(config('erp.lead_sources'))->push($lead->source)->filter()->unique()->values())
                        @foreach($sourceOptions as $s)
                            <option value="{{ $s }}" @selected(old('source', $lead->source)===$s)>{{ ucfirst($s) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Campaign Name</label>
                    <input type="text" name="campaign_name" value="{{ old('campaign_name', $lead->campaign_name) }}" class="ks-form-input" placeholder="e.g. Facebook Ads - Sep 2026 / LinkedIn Campaign">
                </div>
                <div>
                    <label class="ks-label">Department</label>
                    <select name="department" class="ks-form-input">
                        @foreach(config('erp.departments') as $d)
                            <option value="{{ $d }}" @selected(old('department', $lead->department)===$d)>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Priority</label>
                    <select name="priority" class="ks-form-input">
                        @foreach(['low','medium','high','urgent'] as $p)
                            <option value="{{ $p }}" @selected(old('priority', $lead->priority)===$p)>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="ks-label">Assign To</label>
                    <select name="assigned_to" class="ks-form-input">
                        <option value="">Unassigned</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}" @selected(old('assigned_to', $lead->assigned_to)==$u->id)>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="ks-label">Notes</label>
                <textarea name="notes" rows="3" class="ks-form-input">{{ old('notes', $lead->notes) }}</textarea>
            </div>
            <div class="flex gap-3">
                <button type="submit" class="ks-btn ks-btn-primary"><i class="ti ti-check" style="font-size:14px;"></i> Update Lead</button>
                <a href="{{ route('erp.leads.show', $lead) }}" class="ks-btn ks-btn-light">Cancel</a>
            </div>
        </form>
    </div>
</div>
@endsection
