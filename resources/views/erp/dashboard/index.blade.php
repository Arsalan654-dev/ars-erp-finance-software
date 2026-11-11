@extends('erp.layouts.app')

@section('title', 'Dashboard')

@section('content')
@php $unreadCount = auth()->user()->notifications()->where('is_read', false)->count(); @endphp

{{-- ===== Breadcrumb / Page header ===== --}}
<div class="ks-page-header">
    <div>
        <h1 class="ks-page-title">{{ $pageTitle ?? 'Dashboard' }}</h1>
        <p class="ks-page-sub">{{ $pageSubtitle ?? 'Welcome back — here is your operational overview.' }}</p>
    </div>
    <div class="ks-page-actions">
        <span class="ks-badge ks-badge-blue"><i class="ti ti-calendar me-1" style="font-size:13px;"></i>{{ now()->format('l, F d, Y') }}</span>
    </div>
</div>

@php
    $iconClients = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
    $iconTasks = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>';
    $iconInvoice = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>';
    $iconMoney = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>';
    $iconUsers = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
    $iconCalendar = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>';
    $iconChart = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';
    $iconAlert = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
    $iconCheck = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $iconClock = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
    $iconGraduation = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>';
    $iconTrendUp = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>';
    $iconShield = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>';
    $iconRefresh = '<svg style="width:22px;height:22px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>';
@endphp

{{-- ==================== SUPER ADMIN ==================== --}}
@if($view === 'super_admin')
    {{-- Kanakku hero row: Total Revenue gradient + Invoice Overview chart --}}
    <div class="ks-hero-grid">
        <x-stat-card hero label="Total Invoiced" value="{{ erp_money(\App\Models\Invoice::sum('total'), 'PKR') }}" sub="Revenue across all time" href="{{ route('erp.invoices.index') }}" />
        <div class="ks-card">
            <div class="ks-card-header">
                <div>
                    <p class="ks-card-title" style="margin:0;">Invoice Overview</p>
                    <p class="ks-card-subtitle">Last 6 months</p>
                </div>
                <span class="ks-badge ks-badge-green"><i class="ti ti-trending-up me-1"></i>{{ count($revenueTrend['labels'] ?? []) }} months</span>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="saRevenueChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Stat cards grid --}}
    <div class="ks-stat-grid">
        <x-stat-card label="Total Clients" value="{{ $totalClients }}" color="indigo" icon="{!! $iconClients !!}" href="{{ route('erp.clients.index') }}" sub="All registered clients" />
        <x-stat-card label="Open Tasks" value="{{ $openTasks }}" color="blue" icon="{!! $iconTasks !!}" href="{{ route('erp.tasks.index') }}" sub="Across all departments" />
        <x-stat-card label="Pending Payments" value="{{ $pendingPayments }}" color="yellow" icon="{!! $iconMoney !!}" href="{{ route('erp.finance.payments') }}" sub="Awaiting verification" />
        <x-stat-card label="Outstanding Invoices" value="{{ $outstandingInvoices }}" color="red" icon="{!! $iconAlert !!}" href="{{ route('erp.invoices.index') }}" sub="Unpaid balance > 0" />
        <x-stat-card label="Overdue Tasks" value="{{ $overdueTasks }}" color="orange" icon="{!! $iconClock !!}" href="{{ route('erp.tasks.index') }}" sub="Past due date" />
        <x-stat-card label="Upcoming Renewals" value="{{ $upcomingRenewals }}" color="purple" icon="{!! $iconRefresh !!}" href="{{ route('erp.compliance.index') }}" sub="Within next 60 days" />
        <x-stat-card label="Active Clients" value="{{ $activeClients }}" color="green" icon="{!! $iconCheck !!}" href="{{ route('erp.clients.index') }}" sub="Currently active" />
        <x-stat-card label="Employees" value="{{ $employeesCount }}" color="teal" icon="{!! $iconUsers !!}" href="{{ route('erp.hr.index') }}" sub="Active users" />
    </div>

    @include('erp.dashboard.partials.pipeline-widget', ['pipelineCounts' => $pipelineCounts ?? null])

    {{-- Charts row --}}
    <div class="ks-grid-2">
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Tasks by Status</p><p class="ks-card-subtitle">Current workload distribution</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="saTaskChart"></canvas></div>
            </div>
        </div>
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Clients by Status</p><p class="ks-card-subtitle">Portfolio health</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="saClientChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Recent lists row --}}
    <div class="ks-grid-2">
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Recent Clients</p>
                <a href="{{ route('erp.clients.index') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
            </div>
            <div class="ks-card-body-0">
                <ul class="ks-list">
                    @forelse($recentClients as $c)
                        <li>
                            <div class="ks-list-main">
                                <p class="ks-list-title">{{ $c->business_name }}</p>
                                <p class="ks-list-sub">{{ $c->email }}</p>
                            </div>
                            <span class="ks-list-meta">{{ $c->created_at->diffForHumans() }}</span>
                        </li>
                    @empty
                        <li><span style="color:var(--gray-400);font-size:13px;">No clients yet.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Recent Tasks</p>
                <a href="{{ route('erp.tasks.index') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
            </div>
            <div class="ks-card-body-0">
                <ul class="ks-list">
                    @forelse($recentTasks as $t)
                        <li>
                            <div class="ks-list-main">
                                <p class="ks-list-title">{{ $t->title }}</p>
                                <p class="ks-list-sub">{{ $t->task_number }}</p>
                            </div>
                            {!! erp_status_badge($t->status) !!}
                        </li>
                    @empty
                        <li><span style="color:var(--gray-400);font-size:13px;">No tasks yet.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

{{-- ==================== HR ==================== --}}
@elseif($view === 'hr')
    <div class="ks-hero-grid">
        <x-stat-card hero label="Total Employees" value="{{ $totalEmployees }}" sub="{{ $activeEmployees }} active" href="{{ route('erp.hr.index') }}" />
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Attendance — Last 7 Days</p><p class="ks-card-subtitle">Present vs absent</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="hrAttendanceChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="ks-stat-grid">
        <x-stat-card label="Active" value="{{ $activeEmployees }}" color="green" icon="{!! $iconCheck !!}" href="{{ route('erp.hr.index') }}" sub="Currently active" />
        <x-stat-card label="Present Today" value="{{ $presentToday }}" color="blue" icon="{!! $iconCalendar !!}" href="{{ route('erp.hr.attendance') }}" sub="Marked present" />
        <x-stat-card label="Absent Today" value="{{ $absentToday }}" color="red" icon="{!! $iconAlert !!}" href="{{ route('erp.hr.attendance') }}" sub="Absent / on leave" />
        <x-stat-card label="Pending Training" value="{{ $pendingTraining }}" color="yellow" icon="{!! $iconGraduation !!}" href="{{ route('erp.hr.training') }}" sub="Not completed yet" />
        <x-stat-card label="Upcoming Joiners" value="{{ $upcomingJoiners }}" color="purple" icon="{!! $iconTrendUp !!}" href="{{ route('erp.hr.index') }}" sub="Joining soon" />
        <x-stat-card label="Payroll Pending" value="{{ $payrollPending }}" color="orange" icon="{!! $iconMoney !!}" href="{{ route('erp.hr.payroll') }}" sub="Awaiting approval" />
        <x-stat-card label="Leave Requests" value="{{ $leavePending ?? 0 }}" color="teal" icon="{!! $iconClock !!}" href="{{ route('erp.hr.leave') }}" sub="Pending decision" />
        <x-stat-card label="On Leave Today" value="{{ $onLeaveToday }}" color="indigo" icon="{!! $iconUsers !!}" href="{{ route('erp.hr.leave') }}" sub="Approved leave" />
    </div>

    <div class="ks-grid-2">
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Employees by Department</p><p class="ks-card-subtitle">Team distribution</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="hrDeptChart"></canvas></div>
            </div>
        </div>
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Recent Employees</p>
                <a href="{{ route('erp.hr.index') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
            </div>
            <div class="ks-card-body-0">
                <ul class="ks-list">
                    @forelse($recentEmployees as $e)
                        <li>
                            <div class="ks-list-main">
                                <p class="ks-list-title">{{ $e->user->name ?? 'N/A' }}</p>
                                <p class="ks-list-sub">{{ $e->department ?? 'N/A' }}</p>
                            </div>
                            {!! erp_status_badge($e->status) !!}
                        </li>
                    @empty
                        <li><span style="color:var(--gray-400);font-size:13px;">No employees.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

{{-- ==================== FINANCE ==================== --}}
@elseif($view === 'finance')
    <div class="ks-hero-grid">
        <x-stat-card hero label="Monthly Revenue" value="{{ erp_money($monthlyRevenue, 'PKR') }}" sub="Invoiced this month" href="{{ route('erp.invoices.index') }}" />
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Revenue vs Expenses</p><p class="ks-card-subtitle">Last 6 months</p></div>
                <a href="{{ route('erp.finance.reports') }}" class="ks-btn ks-btn-light ks-btn-sm">Full report</a>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="finRevExpChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="ks-stat-grid">
        <x-stat-card label="Net Position" value="{{ erp_money($netPosition ?? 0, 'PKR') }}" color="indigo" icon="{!! $iconChart !!}" href="{{ route('erp.finance.reports') }}" sub="Revenue − expenses" />
        <x-stat-card label="Paid This Month" value="{{ erp_money($paidThisMonth, 'PKR') }}" color="green" icon="{!! $iconMoney !!}" href="{{ route('erp.finance.payments') }}" sub="Verified payments" />
        <x-stat-card label="Outstanding" value="{{ erp_money($outstanding, 'PKR') }}" color="red" icon="{!! $iconAlert !!}" href="{{ route('erp.invoices.index') }}" sub="Unpaid balance" />
        <x-stat-card label="Receivables" value="{{ $receivables }}" color="purple" icon="{!! $iconInvoice !!}" href="{{ route('erp.invoices.index') }}" sub="Open invoices" />
        <x-stat-card label="Pending Verification" value="{{ $pendingVerification }}" color="yellow" icon="{!! $iconClock !!}" href="{{ route('erp.finance.payments') }}" sub="Payments to check" />
        <x-stat-card label="Expenses (Month)" value="{{ erp_money($expensesThisMonth, 'PKR') }}" color="orange" icon="{!! $iconShield !!}" href="{{ route('erp.finance.expenses') }}" sub="Approved expenses" />
        <x-stat-card label="Avg / Invoice" value="{{ $receivables > 0 ? erp_money(round($outstanding / $receivables), 'PKR') : erp_money(0, 'PKR') }}" color="teal" icon="{!! $iconChart !!}" href="{{ route('erp.finance.ledger') }}" sub="Per open invoice" />
        <x-stat-card label="Currency Mix" value="{{ $currencyBreakdown->count() }}" color="cyan" icon="{!! $iconMoney !!}" href="{{ route('erp.finance.exchange-rates') }}" sub="Currencies in use" />
    </div>

    <div class="ks-grid-2">
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Payment Status</p><p class="ks-card-subtitle">All payments by state</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="finPayChart"></canvas></div>
            </div>
        </div>
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Currency Breakdown</p><p class="ks-card-subtitle">Verified payments (month)</p></div>
            </div>
            <div class="ks-card-body">
                @if($currencyBreakdown->count())
                    <ul class="ks-list">
                        @foreach($currencyBreakdown as $cb)
                            <li>
                                <span class="ks-list-title">{{ $cb->currency }}</span>
                                <span class="ks-list-meta"><b>{{ erp_money($cb->total, $cb->currency) }}</b></span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p style="color:var(--gray-400);font-size:13px;">No verified payments this month.</p>
                @endif
            </div>
        </div>
    </div>

    <div class="ks-grid-2">
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Recent Payments</p>
                <a href="{{ route('erp.finance.payments') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
            </div>
            <div class="ks-card-body-0">
                <ul class="ks-list">
                    @forelse($recentPayments as $p)
                        <li>
                            <div class="ks-list-main">
                                <p class="ks-list-title">{{ $p->client->business_name ?? 'N/A' }}</p>
                                <p class="ks-list-sub">{{ $p->payment_number }} — {{ erp_money($p->amount, $p->currency) }}</p>
                            </div>
                            {!! erp_status_badge($p->status) !!}
                        </li>
                    @empty
                        <li><span style="color:var(--gray-400);font-size:13px;">No payments.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Recent Invoices</p>
                <a href="{{ route('erp.invoices.index') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
            </div>
            <div class="ks-card-body-0">
                <ul class="ks-list">
                    @forelse($recentInvoices as $inv)
                        <li>
                            <div class="ks-list-main">
                                <p class="ks-list-title">{{ $inv->client->business_name ?? 'N/A' }}</p>
                                <p class="ks-list-sub">{{ $inv->invoice_number }} — {{ erp_money($inv->total, $inv->currency ?? 'PKR') }}</p>
                            </div>
                            {!! erp_status_badge($inv->status) !!}
                        </li>
                    @empty
                        <li><span style="color:var(--gray-400);font-size:13px;">No invoices.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

{{-- ==================== CSR MANAGER ==================== --}}
@elseif($view === 'csr_manager')
    <div class="ks-hero-grid">
        <x-stat-card hero label="Open Tasks" value="{{ $openTasks }}" sub="{{ $overdueTasks }} overdue" href="{{ route('erp.tasks.board') }}" />
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Tasks by Department</p><p class="ks-card-subtitle">Open workload split</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="csrDeptChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="ks-stat-grid">
        <x-stat-card label="Total Clients" value="{{ $totalClients }}" color="indigo" icon="{!! $iconClients !!}" href="{{ route('erp.clients.index') }}" sub="All clients" />
        <x-stat-card label="New This Month" value="{{ $newClients }}" color="green" icon="{!! $iconTrendUp !!}" href="{{ route('erp.clients.index') }}" sub="Added this month" />
        <x-stat-card label="Completed (Month)" value="{{ $completedTasks ?? 0 }}" color="teal" icon="{!! $iconCheck !!}" href="{{ route('erp.tasks.index') }}" sub="Finished this month" />
        <x-stat-card label="US Tax" value="{{ $usTaxTasks }}" color="cyan" icon="{!! $iconTasks !!}" href="{{ route('erp.tasks.board') }}" sub="Open US tax tasks" />
        <x-stat-card label="UK Tax" value="{{ $ukTaxTasks }}" color="purple" icon="{!! $iconTasks !!}" href="{{ route('erp.tasks.board') }}" sub="Open UK tax tasks" />
        <x-stat-card label="Legal" value="{{ $legalTasks }}" color="orange" icon="{!! $iconShield !!}" href="{{ route('erp.tasks.board') }}" sub="Open legal tasks" />
        <x-stat-card label="Overdue Tasks" value="{{ $overdueTasks }}" color="red" icon="{!! $iconAlert !!}" href="{{ route('erp.tasks.index') }}" sub="Past due date" />
        <x-stat-card label="Task Status Mix" value="{{ count($taskStatusBreakdown ?? []) }}" color="yellow" icon="{!! $iconChart !!}" href="{{ route('erp.tasks.board') }}" sub="Distinct states" />
    </div>

    @include('erp.dashboard.partials.pipeline-widget', ['pipelineCounts' => $pipelineCounts ?? null])

    <div class="ks-grid-2">
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">Task Status Mix</p><p class="ks-card-subtitle">All tasks by state</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="csrTaskChart"></canvas></div>
            </div>
        </div>
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">Upcoming Deadlines (7 days)</p>
                <a href="{{ route('erp.tasks.index') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
            </div>
            <div class="ks-card-body-0">
                <ul class="ks-list">
                    @forelse($upcomingDeadlines as $t)
                        <li>
                            <div class="ks-list-main">
                                <p class="ks-list-title">{{ $t->title }}</p>
                                <p class="ks-list-sub">Due {{ $t->due_date?->format('M d, Y') }}</p>
                            </div>
                            {!! erp_status_badge($t->priority) !!}
                        </li>
                    @empty
                        <li><span style="color:var(--gray-400);font-size:13px;">No upcoming deadlines.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">Recent Tasks</p>
            <a href="{{ route('erp.tasks.index') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
        </div>
        <div class="ks-card-body-0">
            <ul class="ks-list">
                @forelse($recentTasks as $t)
                    <li>
                        <div class="ks-list-main">
                            <p class="ks-list-title">{{ $t->title }}</p>
                            <p class="ks-list-sub">{{ $t->client->business_name ?? 'N/A' }} · {{ $t->task_number }}</p>
                        </div>
                        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                            <span class="ks-list-meta">{{ $t->department ?? '' }}</span>
                            {!! erp_status_badge($t->status) !!}
                        </div>
                    </li>
                @empty
                    <li><span style="color:var(--gray-400);font-size:13px;">No tasks.</span></li>
                @endforelse
            </ul>
        </div>
    </div>

{{-- ==================== TEAM ASSOCIATE ==================== --}}
@elseif($view === 'team_associate')
    <div class="ks-hero-grid">
        <x-stat-card hero label="My Active Tasks" value="{{ $myTasks }}" sub="{{ $completed }} completed this month" href="{{ route('erp.tasks.index') }}" />
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">My Completed Tasks</p><p class="ks-card-subtitle">Last 6 months</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="assocTrendChart"></canvas></div>
            </div>
        </div>
    </div>

    <div class="ks-stat-grid ks-stat-grid-5">
        <x-stat-card label="Due Today" value="{{ $dueToday }}" color="orange" icon="{!! $iconClock !!}" href="{{ route('erp.tasks.index') }}" sub="Deadline is today" />
        <x-stat-card label="Upcoming" value="{{ $upcoming }}" color="blue" icon="{!! $iconCalendar !!}" href="{{ route('erp.tasks.index') }}" sub="Future deadlines" />
        <x-stat-card label="Overdue" value="{{ $overdue }}" color="red" icon="{!! $iconAlert !!}" href="{{ route('erp.tasks.index') }}" sub="Past due date" />
        <x-stat-card label="Completed (Month)" value="{{ $completed }}" color="green" icon="{!! $iconCheck !!}" href="{{ route('erp.tasks.index') }}" sub="This month" />
        <x-stat-card label="Task States" value="{{ count($myTaskStatusBreakdown ?? []) }}" color="purple" icon="{!! $iconChart !!}" href="{{ route('erp.tasks.index') }}" sub="Where my work stands" />
    </div>

    <div class="ks-grid-2">
        <div class="ks-card">
            <div class="ks-card-header">
                <div><p class="ks-card-title" style="margin:0;">My Task Status</p><p class="ks-card-subtitle">Current state of my work</p></div>
            </div>
            <div class="ks-card-body">
                <div class="ks-h-56"><canvas id="assocStatusChart"></canvas></div>
            </div>
        </div>
        <div class="ks-card">
            <div class="ks-card-header">
                <p class="ks-card-title" style="margin:0;">My KPI — {{ now()->format('F Y') }}</p>
            </div>
            <div class="ks-card-body-0">
                <ul class="ks-list">
                    @forelse($myKpis as $kpi)
                        <li>
                            <span class="ks-list-title">{{ $kpi->kpiDefinition->name ?? 'N/A' }}</span>
                            <span class="ks-list-meta">
                                <b>{{ $kpi->actual_value ?? '—' }} {{ $kpi->kpiDefinition->unit ?? '' }}</b>
                                @if($kpi->score)
                                    <span class="ks-badge ks-badge-blue" style="margin-left:6px;">{{ $kpi->score }}%</span>
                                @endif
                            </span>
                        </li>
                    @empty
                        <li><span style="color:var(--gray-400);font-size:13px;">No KPI data for this month.</span></li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>

    <div class="ks-card">
        <div class="ks-card-header">
            <p class="ks-card-title" style="margin:0;">My Assigned Tasks</p>
            <a href="{{ route('erp.tasks.index') }}" class="ks-btn ks-btn-light ks-btn-sm">View all</a>
        </div>
        <div class="ks-card-body-0">
            <ul class="ks-list">
                @forelse($assignedTasks as $t)
                    <li>
                        <div class="ks-list-main">
                            <p class="ks-list-title">{{ $t->title }}</p>
                            <p class="ks-list-sub">Due {{ $t->due_date?->format('M d, Y') ?: 'No date' }} · {{ $t->client->business_name ?? '' }}</p>
                        </div>
                        {!! erp_status_badge($t->status) !!}
                    </li>
                @empty
                    <li><span style="color:var(--gray-400);font-size:13px;">No tasks assigned.</span></li>
                @endforelse
            </ul>
        </div>
    </div>
@endif
@endsection

@push('styles')
<style>
/* Dashboard hero grid responsive */
.ks-hero-grid {
    display: grid !important;
    grid-template-columns: 1.1fr 2.3fr !important;
    gap: 22px;
    margin-bottom: 22px;
}
@media (max-width: 1199px) {
    .ks-hero-grid {
        grid-template-columns: 1fr !important;
        gap: 16px;
    }
}
@media (max-width: 767px) {
    .ks-hero-grid {
        grid-template-columns: 1fr !important;
        gap: 14px;
        margin-bottom: 16px;
    }
    .ks-hero-grid .ks-hero {
        padding: 16px;
        border-radius: 10px;
    }
    .ks-hero-grid .ks-hero-value {
        font-size: 22px;
    }
    .ks-hero-grid .ks-hero-label {
        font-size: 12px;
    }
    .ks-hero-grid .ks-hero-btn {
        padding: 7px 14px;
        font-size: 12px;
    }
    .ks-hero-grid .ks-card {
        margin-bottom: 0;
    }
}
@media (max-width: 480px) {
    .ks-hero-grid {
        gap: 12px;
        margin-bottom: 14px;
    }
    .ks-hero-grid .ks-hero {
        padding: 14px;
        border-radius: 8px;
    }
    .ks-hero-grid .ks-hero-value {
        font-size: 20px;
    }
    .ks-hero-grid .ks-hero-label {
        font-size: 11px;
    }
    .ks-hero-grid .ks-hero-btn {
        padding: 6px 12px;
        font-size: 11px;
    }
}

/* Page header responsive */
.ks-page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    margin-bottom: 22px;
}
@media (max-width: 767px) {
    .ks-page-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
        margin-bottom: 18px;
    }
    .ks-page-header .ks-page-actions {
        width: 100%;
    }
    .ks-page-header .ks-page-actions .ks-badge {
        width: 100%;
        justify-content: center;
    }
}
@media (max-width: 480px) {
    .ks-page-header {
        gap: 8px;
        margin-bottom: 14px;
    }
}

/* Team associate 5-column stat grid */
.ks-stat-grid-5 {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 22px;
    margin-bottom: 22px;
}
@media (max-width: 1399px) {
    .ks-stat-grid-5 {
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }
}
@media (max-width: 991px) {
    .ks-stat-grid-5 {
        grid-template-columns: repeat(2, 1fr);
        gap: 14px;
    }
}
@media (max-width: 575px) {
    .ks-stat-grid-5 {
        grid-template-columns: 1fr;
        gap: 12px;
    }
}

/* Charts responsive */
.ks-h-56 canvas,
.ks-h-64 canvas {
    width: 100% !important;
    height: 100% !important;
}

/* Stat grid responsive on dashboard */
@media (max-width: 767px) {
    .ks-stat-grid {
        grid-template-columns: 1fr 1fr !important;
        gap: 14px !important;
    }
}
@media (max-width: 480px) {
    .ks-stat-grid {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
    }
}

/* Grid-2 responsive on dashboard */
@media (max-width: 767px) {
    .ks-grid-2 {
        grid-template-columns: 1fr !important;
        gap: 16px !important;
    }
}
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var cs = getComputedStyle(document.body);
    var roleAccent = cs.getPropertyValue('--role-grad-2').trim() || '#2563EB';
    var roleAccent2 = cs.getPropertyValue('--role-grad-4').trim() || '#60A5FA';
    var textColor = '#6f7180';
    var gridColor = 'rgba(20,10,50,0.06)';

    Chart.defaults.font.family = 'Inter, sans-serif';
    Chart.defaults.color = textColor;

    function mkBar(id, labels, datasets) {
        var el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'bar',
            data: { labels: labels, datasets: datasets },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { labels: { boxWidth: 10, font: { size: 11 } } }
                },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { grid: { color: gridColor }, ticks: { font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }
    function mkDoughnut(id, labels, values) {
        var el = document.getElementById(id);
        if (!el) return;
        var palette = [roleAccent, roleAccent2, '#F59E0B', '#EF4444', '#10B981', '#6366F1', '#06B6D4', '#8B5CF6'];
        new Chart(el, {
            type: 'doughnut',
            data: { labels: labels, datasets: [{ data: values, backgroundColor: palette.slice(0, values.length), borderWidth: 2, borderColor: '#fff' }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '62%',
                plugins: { legend: { position: 'bottom', labels: { boxWidth: 10, padding: 12, font: { size: 10 } } } }
            }
        });
    }
    function mkLine(id, labels, values, color) {
        var el = document.getElementById(id);
        if (!el) return;
        new Chart(el, {
            type: 'line',
            data: { labels: labels, datasets: [{ data: values, borderColor: color, backgroundColor: color + '22', fill: true, tension: 0.4, pointBackgroundColor: color, pointRadius: 3 }] },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { grid: { color: gridColor }, ticks: { font: { size: 10 } }, beginAtZero: true }
                }
            }
        });
    }

    @if($view === 'super_admin')
        mkBar('saRevenueChart', @json($revenueTrend['labels'] ?? []), [{ label: 'Revenue', data: @json($revenueTrend['values'] ?? []), backgroundColor: roleAccent + 'cc', borderRadius: 6 }]);
        @php $ts = collect($taskStatusBreakdown ?? []); @endphp
        mkDoughnut('saTaskChart', @json($ts->pluck('label')), @json($ts->pluck('value')));
        @php $cst = collect($clientStatusBreakdown ?? []); @endphp
        mkDoughnut('saClientChart', @json($cst->pluck('label')), @json($cst->pluck('value')));
    @endif

    @if($view === 'hr')
        mkBar('hrAttendanceChart', @json($attendanceTrend['labels'] ?? []), [
            { label: 'Present', data: @json($attendanceTrend['present'] ?? []), backgroundColor: '#10B981', borderRadius: 4 },
            { label: 'Absent / Leave', data: @json($attendanceTrend['absent'] ?? []), backgroundColor: '#EF4444', borderRadius: 4 }
        ]);
        @php $db = collect($departmentBreakdown ?? []); @endphp
        mkDoughnut('hrDeptChart', @json($db->pluck('label')), @json($db->pluck('value')));
    @endif

    @if($view === 'finance')
        mkBar('finRevExpChart', @json($revenueTrend['labels'] ?? []), [
            { label: 'Revenue', data: @json($revenueTrend['values'] ?? []), backgroundColor: roleAccent + 'cc', borderRadius: 6 },
            { label: 'Expenses', data: @json($expenseTrend['values'] ?? []), backgroundColor: '#F59E0B', borderRadius: 6 }
        ]);
        @php $ps = collect($paymentStatusBreakdown ?? []); @endphp
        mkDoughnut('finPayChart', @json($ps->pluck('label')), @json($ps->pluck('value')));
    @endif

    @if($view === 'csr_manager')
        @php $cdb = collect($departmentBreakdown ?? []); @endphp
        mkDoughnut('csrDeptChart', @json($cdb->pluck('label')), @json($cdb->pluck('value')));
        @php $cts = collect($taskStatusBreakdown ?? []); @endphp
        mkDoughnut('csrTaskChart', @json($cts->pluck('label')), @json($cts->pluck('value')));
    @endif

    @if($view === 'team_associate')
        mkLine('assocTrendChart', @json($myCompletedTrend['labels'] ?? []), @json($myCompletedTrend['values'] ?? []), roleAccent);
        @php $mts = collect($myTaskStatusBreakdown ?? []); @endphp
        mkDoughnut('assocStatusChart', @json($mts->pluck('label')), @json($mts->pluck('value')));
    @endif
});
</script>
@endpush
