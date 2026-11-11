@extends('erp.layouts.app') @section('title', 'Settings')
@section('content')
<div class="reveal max-w-2xl mx-auto">
 <h1 class="ks-page-title">System Settings</h1>
 <div class="ks-card"><div class="ks-card-body">
 @can('settings.manage')
 <form method="POST" action="{{ route('erp.admin.settings.update') }}" class="space-y-6">
 @csrf
 <div>
 <h3 class="text-sm font-semibold text-gray-700 mb-3">Session Security</h3>
 <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
 <div><label class="block text-sm font-medium text-gray-700 mb-1">Idle Timeout (minutes)</label><input type="number" value="{{ config('erp.session_idle_timeout') }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm"><p class="text-xs text-gray-400 mt-1">Set via ERP_SESSION_IDLE_TIMEOUT env</p></div>
 </div>
 </div>
 <div>
 <h3 class="text-sm font-semibold text-gray-700 mb-3">Default Exchange Rates (→ PKR)</h3>
 <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
 @foreach(['USD','GBP','EUR'] as $cur)
 <div><label class="block text-sm font-medium text-gray-700 mb-1">1 {{ $cur }}</label><input type="number" step="0.01" value="{{ config('erp.default_exchange_rates.'.$cur) }}" disabled class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm"><p class="text-xs text-gray-400 mt-1">Set in config/erp.php</p></div>
 @endforeach
 </div>
 </div>
 <div class="bg-indigo-50 border border-indigo-100 rounded-lg p-4 text-sm text-indigo-700">
 <strong>Security note:</strong> This ERP enforces AES-256 encryption at rest, TLS 1.3 in transit, single active sessions, idle timeouts, data masking for sensitive identifiers, and append-only audit logging.
 </div>
 <button type="submit" class="px-6 py-2.5 consultiser-gradient hover:opacity-95 text-white font-semibold rounded-lg">Save Settings</button>
 </form>
 @endcan
 </div>
</div></div>@endsection