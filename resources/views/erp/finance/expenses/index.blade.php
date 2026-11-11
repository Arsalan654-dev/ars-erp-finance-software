@extends('erp.layouts.app')
@section('title', 'Expenses')
@section('content')

<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">Operational Expenses</h1>
        <p class="ks-page-sub">Track and approve multi-currency operational expenses with ledger integration.</p>
    </div>
    <div class="ks-page-actions">
        @can('expense.create')
        <a href="{{ route('erp.finance.expenses.create') }}" class="ks-btn ks-btn-primary"><i class="ti ti-plus" style="font-size:15px;"></i> Record Expense</a>
        @endcan
    </div>
</div>

{{-- Filters --}}
<div class="ks-card">
    <div class="ks-card-body" style="padding:16px 20px;">
        <form method="GET" class="ks-filter-bar">
            <div class="ks-filter-search" style="position:relative;">
                <i class="ti ti-search" style="position:absolute;left:12px;top:50%;transform:translateY(-50%);color:var(--gray-400);font-size:15px;"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Search expense number..." class="ks-form-input" style="padding-left:36px;">
            </div>
            <select name="status" class="ks-form-input" style="width:140px;">
                <option value="">All Status</option>
                @foreach(['pending','approved','rejected'] as $s)<option value="{{ $s }}" @selected(request('status')===$s)>{{ ucfirst($s) }}</option>@endforeach
            </select>
            <button type="submit" class="ks-btn ks-btn-primary">Filter</button>
        </form>
    </div>
</div>

{{-- Table --}}
<div class="ks-card">
    <div class="ks-card-header">
        <p class="ks-card-title" style="margin:0;">All Expenses <span class="ks-badge ks-badge-blue" style="margin-left:8px;">{{ $expenses->total() }}</span></p>
    </div>
    <div class="ks-card-body-0">
        <div class="ks-table-wrap">
            <table class="ks-table ks-table-responsive">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th style="text-align:right;">Amount</th>
                        <th style="text-align:right;">PKR Equiv.</th>
                        <th>Date</th>
                        <th style="text-align:center;">Status</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $e)
                        <tr>
                            <td>{{ $e->expense_number }}</td>
                            <td>{{ $e->category ?? '—' }}</td>
                            <td style="text-align:right;" class="ks-strong">{{ erp_money($e->amount, $e->currency) }}</td>
                            <td style="text-align:right;">{{ erp_money($e->pkr_equivalent, 'PKR') }}</td>
                            <td>{{ $e->expense_date?->format('Y-m-d') }}</td>
                            <td style="text-align:center;">{!! erp_status_badge($e->status) !!}</td>
                            <td style="text-align:right;">
                                @if($e->status === 'pending')
                                    @can('expense.approve')
                                    <form method="POST" action="{{ route('erp.finance.expenses.approve', $e) }}" style="display:inline;">
                                        @csrf
                                        <button type="submit" class="ks-btn ks-btn-primary ks-btn-sm"><i class="ti ti-check" style="font-size:14px;"></i> Approve</button>
                                    </form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="text-align:center;color:var(--gray-400);padding:40px;">No expenses recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div style="padding:14px 20px;border-top:1px solid var(--gray-100);">
        {{ $expenses->links() }}
    </div>
</div>
@endsection