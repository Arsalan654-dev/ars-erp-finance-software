@extends('erp.layouts.app')
@section('title', 'Email Settings')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Email Settings</h1>
        <p class="ks-page-sub">Configure SMTP credentials for outgoing emails. No need to edit .env — set it here.</p>
    </div>
</div>

<div style="max-width:700px;">
    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title">SMTP Configuration</p>
        </div>
        <div class="ks-card-body">
            @if($errors->any())
                <div class="ks-alert ks-alert-error">{{ $errors->first() }}</div>
            @endif
            @if(session('success'))
                <div class="ks-alert ks-alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="ks-alert ks-alert-error">{{ session('error') }}</div>
            @endif

            @can('settings.manage')
            <form method="POST" action="{{ route('erp.admin.email-settings.update') }}" class="space-y-5">
                @csrf

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label class="ks-form-label">SMTP Host</label>
                        <input type="text" name="host" value="{{ old('host', $emailSetting->host) }}" required class="ks-form-input" placeholder="smtp.gmail.com">
                        <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Gmail: smtp.gmail.com | Outlook: smtp.office365.com</p>
                    </div>
                    <div>
                        <label class="ks-form-label">Port</label>
                        <input type="number" name="port" value="{{ old('port', $emailSetting->port) }}" required class="ks-form-input" placeholder="587">
                        <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">TLS: 587 | SSL: 465</p>
                    </div>
                </div>

                <div>
                    <label class="ks-form-label">Encryption</label>
                    <select name="encryption" class="ks-form-input">
                        <option value="tls" {{ old('encryption', $emailSetting->encryption) === 'tls' ? 'selected' : '' }}>TLS (Recommended)</option>
                        <option value="ssl" {{ old('encryption', $emailSetting->encryption) === 'ssl' ? 'selected' : '' }}>SSL</option>
                        <option value="none" {{ old('encryption', $emailSetting->encryption) === 'none' ? 'selected' : '' }}>None</option>
                    </select>
                </div>

                <div>
                    <label class="ks-form-label">Email Address (Username)</label>
                    <input type="email" name="username" value="{{ old('username', $emailSetting->username) }}" required class="ks-form-input" placeholder="your-email@gmail.com">
                </div>

                <div>
                    <label class="ks-form-label">App Password / Passkey</label>
                    <input type="password" name="password" value="{{ old('password', $emailSetting->password) }}" {{ $emailSetting->password ? '' : 'required' }} class="ks-form-input" placeholder="{{ $emailSetting->password ? '•••••••• (saved — leave blank to keep)' : 'Enter your app password' }}">
                    @if($emailSetting->password)
                        <p style="font-size:11px;color:var(--green-500);margin-top:4px;">✅ Password saved. Leave blank to keep current.</p>
                    @else
                        <p style="font-size:11px;color:var(--gray-400);margin-top:4px;">Gmail: Generate an App Password in Google Account → Security → 2FA → App Passwords</p>
                    @endif
                </div>

                <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
                    <div>
                        <label class="ks-form-label">From Address</label>
                        <input type="email" name="from_address" value="{{ old('from_address', $emailSetting->from_address) }}" required class="ks-form-input" placeholder="noreply@consultiser.com">
                    </div>
                    <div>
                        <label class="ks-form-label">From Name</label>
                        <input type="text" name="from_name" value="{{ old('from_name', $emailSetting->from_name) }}" required class="ks-form-input" placeholder="Consultiser ERP">
                    </div>
                </div>

                <div style="display:flex;gap:12px;align-items:center;">
                    <button type="submit" class="ks-btn ks-btn-primary">
                        <i class="ti ti-device-floppy" style="font-size:14px;"></i> Save Settings
                    </button>
                    <button type="button" onclick="document.getElementById('testForm').submit()" class="ks-btn ks-btn-outline">
                        <i class="ti ti-send" style="font-size:14px;"></i> Send Test Email
                    </button>
                </div>
            </form>
            @endcan

            <form id="testForm" method="POST" action="{{ route('erp.admin.email-settings.test') }}" style="display:none;">
                @csrf
            </form>

            @if($emailSetting->username)
                <div style="margin-top:20px;padding:16px;background:var(--gray-50);border:1px solid var(--gray-100);border-radius:10px;">
                    <p style="font-size:12px;color:var(--gray-500);margin:0;">
                        <strong>Current Config:</strong>
                        {{ $emailSetting->username }} @ {{ $emailSetting->host }}:{{ $emailSetting->port }} ({{ strtoupper($emailSetting->encryption) }})
                    </p>
                    <p style="font-size:11px;color:var(--gray-400);margin:6px 0 0;">
                        From: {{ $emailSetting->from_name }} &lt;{{ $emailSetting->from_address }}&gt;
                    </p>
                </div>
            @endif
        </div>
    </div>

    <div class="ks-card" style="margin-top:20px;">
        <div class="ks-card-body">
            <h3 style="font-size:14px;font-weight:600;color:var(--gray-700);margin:0 0 12px;">Gmail Setup Guide</h3>
            <ol style="font-size:13px;color:var(--gray-500);padding-left:18px;line-height:1.8;">
                <li>Go to <strong>myaccount.google.com</strong> → Security</li>
                <li>Enable <strong>2-Step Verification</strong> (required)</li>
                <li>Go to <strong>App passwords</strong> (search in Google Account)</li>
                <li>Select <strong>Mail</strong> and <strong>Other (Custom name)</strong> → name it "Consultiser ERP"</li>
                <li>Copy the <strong>16-character password</strong> and paste it above</li>
            </ol>
        </div>
    </div>
</div>

@endsection
