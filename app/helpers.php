<?php

use App\Services\SensitiveDataService;
use Carbon\Carbon;

if (! function_exists('erp_currency')) {
    function erp_currency(?string $code): string
    {
        return match ($code) {
            'USD' => '$',
            'GBP' => '£',
            'EUR' => '€',
            default => '₨',
        };
    }
}

if (! function_exists('erp_money')) {
    function erp_money($amount, ?string $currency = null): string
    {
        if ($amount === null) {
            return '—';
        }

        $formatted = number_format((float) $amount, 2);

        return $currency ? erp_currency($currency).$formatted : $formatted;
    }
}

if (! function_exists('erp_mask')) {
    /**
     * Mask a sensitive value unless the current user may view it unmasked.
     */
    function erp_mask(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return SensitiveDataService::canViewSensitive() ? $value : SensitiveDataService::mask($value);
    }
}

if (! function_exists('erp_status_badge')) {
    function erp_status_badge(?string $status): string
    {
        if ($status === null || $status === '') {
            return '<span class="ks-badge ks-badge-gray">—</span>';
        }
        $colors = [
            'draft' => 'ks-badge-gray',
            'sent' => 'ks-badge-blue',
            'accepted' => 'ks-badge-green',
            'rejected' => 'ks-badge-red',
            'expired' => 'ks-badge-yellow',
            'converted' => 'ks-badge-indigo',
            'locked' => 'ks-badge-purple',
            'pending' => 'ks-badge-yellow',
            'acknowledged' => 'ks-badge-blue',
            'verified' => 'ks-badge-green',
            'active' => 'ks-badge-green',
            'inactive' => 'ks-badge-gray',
            'assigned' => 'ks-badge-blue',
            'in_progress' => 'ks-badge-cyan',
            'review' => 'ks-badge-orange',
            'completed' => 'ks-badge-green',
            'cancelled' => 'ks-badge-red',
            'paid' => 'ks-badge-green',
            'partially_paid' => 'ks-badge-teal',
            'unpaid' => 'ks-badge-red',
            'overdue' => 'ks-badge-red',
            'expiring_soon' => 'ks-badge-yellow',
            'expired_doc' => 'ks-badge-red',
            'renewed' => 'ks-badge-green',
            'high' => 'ks-badge-red',
            'urgent' => 'ks-badge-red',
            'medium' => 'ks-badge-yellow',
            'low' => 'ks-badge-gray',
            // Lead statuses
            'new' => 'ks-badge-blue',
            'contacted' => 'ks-badge-yellow',
            'follow_up' => 'ks-badge-orange',
            'qualified' => 'ks-badge-green',
            'quotation' => 'ks-badge-purple',
            'lost' => 'ks-badge-red',
            // Mismatch / duplicate flags
            'amount_mismatch' => 'ks-badge-red',
            'duplicate' => 'ks-badge-orange',
            'same_requirements' => 'ks-badge-orange',
        ];

        $class = $colors[$status] ?? 'ks-badge-gray';

        return '<span class="ks-badge '.$class.'">'.ucwords(str_replace('_', ' ', $status)).'</span>';
    }
}

if (! function_exists('erp_period_range')) {
    function erp_period_range(string $period): array
    {
        return [
            Carbon::parse($period.'-01')->startOfMonth(),
            Carbon::parse($period.'-01')->endOfMonth(),
        ];
    }
}

if (! function_exists('erp_navigation')) {
    /**
     * Role-aware sidebar navigation.
     */
    function erp_navigation(): array
    {
        $user = auth()->user();

        if (! $user) {
            return [];
        }

        $isSuper = $user->isSuperAdmin();
        $isHr = $isSuper || $user->hasRole('hr');
        $isFinance = $isSuper || $user->hasRole('finance');
        $isCsr = $isSuper || $user->hasRole('csr_manager');
        $isAssociate = $isSuper || $user->hasRole('team_associate');

        $nav = [];

        if ($isSuper) {
            $nav[] = ['heading' => 'System'];
            $nav[] = ['label' => 'Admin Dashboard', 'route' => 'erp.dashboard', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>'];
            $nav[] = ['label' => 'Users', 'route' => 'erp.admin.users', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'];
            $nav[] = ['label' => 'Roles & Permissions', 'route' => 'erp.admin.roles', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>'];
            $nav[] = ['label' => 'Audit Logs', 'route' => 'erp.admin.audit-logs', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>'];
            $nav[] = ['label' => 'Admin Reports', 'route' => 'erp.admin.reports', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-6m4 6V7m4 10v-4M5 21h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>'];
            $nav[] = ['label' => 'Work Report', 'route' => 'erp.admin.work-report', 'routes' => ['erp.admin.work-report'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'];
            $nav[] = ['label' => 'Settings', 'route' => 'erp.admin.settings', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'];
            $nav[] = ['label' => 'Email Settings', 'route' => 'erp.admin.email-settings', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'];
            $nav[] = ['label' => 'WhatsApp Accounts', 'route' => 'erp.admin.whatsapp.index', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>'];
        }

        // Dashboard link for every logged-in role (top of sidebar)
        if (! $isSuper && $user->hasAnyRole(['hr', 'finance', 'csr_manager', 'team_associate'])) {
            $nav[] = ['heading' => 'Main'];
            $nav[] = ['label' => 'Dashboard', 'route' => 'erp.dashboard', 'routes' => ['erp.dashboard'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l9-9 9 9M5 10v10a1 1 0 001 1h3a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1h3a1 1 0 001-1V10"/></svg>'];
        }
        if ($user->hasPermission('client.read')) {
            $nav[] = ['heading' => 'CRM'];
            $nav[] = ['label' => 'Leads', 'route' => 'erp.leads.index', 'routes' => ['erp.leads.index', 'erp.leads.show', 'erp.leads.create', 'erp.leads.edit'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>'];
            if ($user->hasPermission('lead.read')) {
                $nav[] = ['label' => 'CSR Pipeline', 'route' => 'erp.leads.pipeline', 'routes' => ['erp.leads.pipeline'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'];
            }
            $nav[] = ['label' => 'Clients', 'route' => 'erp.clients.index', 'routes' => ['erp.clients.index', 'erp.clients.show', 'erp.clients.create', 'erp.clients.edit'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'];
        }

        if ($user->hasPermission('quotation.read') || $user->hasPermission('invoice.read')) {
            $nav[] = ['heading' => 'Sales'];
            if ($user->hasPermission('quotation.read')) {
                $nav[] = ['label' => 'Quotations', 'route' => 'erp.quotations.index', 'routes' => ['erp.quotations.index', 'erp.quotations.show', 'erp.quotations.create', 'erp.quotations.edit'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'];
            }
            if ($user->hasPermission('invoice.read')) {
                $nav[] = ['label' => 'Invoices', 'route' => 'erp.invoices.index', 'routes' => ['erp.invoices.index', 'erp.invoices.show', 'erp.invoices.create'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'];
            }
            if ($isSuper || $user->hasRole('csr_manager')) {
                $nav[] = ['label' => 'Sales Packages', 'route' => 'erp.sales-packages.index', 'routes' => ['erp.sales-packages.index', 'erp.sales-packages.create', 'erp.sales-packages.edit'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>'];
            }
        }

        if ($isFinance || $isSuper) {
            $nav[] = ['heading' => 'Finance'];
            $nav[] = ['label' => 'Payments', 'route' => 'erp.finance.payments', 'routes' => ['erp.finance.payments', 'erp.finance.payments.show', 'erp.finance.payments.create'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>'];
            $nav[] = ['label' => 'Bank Verification', 'route' => 'erp.finance.bank-verifications', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'];
            $nav[] = ['label' => 'Expenses', 'route' => 'erp.finance.expenses', 'routes' => ['erp.finance.expenses', 'erp.finance.expenses.create'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'];
            $nav[] = ['label' => 'Ledger', 'route' => 'erp.finance.ledger', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>'];
            $nav[] = ['label' => 'Financial Reports', 'route' => 'erp.finance.reports', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'];
            $nav[] = ['label' => 'Exchange Rates', 'route' => 'erp.finance.exchange-rates', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>'];
            $nav[] = ['label' => 'Invoice Approvals', 'route' => 'erp.finance-approvals.index', 'routes' => ['erp.finance-approvals.index', 'erp.finance-approvals.show'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'];
        }

        if ($user->hasPermission('task.read') || $user->hasPermission('project.read')) {
            $nav[] = ['heading' => 'Operations'];
            if ($user->hasPermission('project.read')) {
                $nav[] = ['label' => 'Projects', 'route' => 'erp.projects.index', 'routes' => ['erp.projects.index', 'erp.projects.show', 'erp.projects.create', 'erp.projects.edit'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'];
            }
            if ($user->hasPermission('task.read')) {
                $nav[] = ['label' => 'Task Board', 'route' => 'erp.tasks.board', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>'];
                $nav[] = ['label' => 'Task Pool', 'route' => 'erp.tasks.pool', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>'];
                $nav[] = ['label' => 'All Tasks', 'route' => 'erp.tasks.index', 'routes' => ['erp.tasks.index', 'erp.tasks.show', 'erp.tasks.create'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>'];
            }
        }

        if ($user->hasPermission('compliance.read') || $user->hasPermission('department.manage')) {
            $nav[] = ['heading' => 'Compliance'];
            if ($user->hasPermission('compliance.read')) {
                $nav[] = ['label' => 'Compliance Records', 'route' => 'erp.compliance.index', 'routes' => ['erp.compliance.index', 'erp.compliance.show', 'erp.compliance.create', 'erp.compliance.edit'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'];
            }
            if ($user->hasPermission('department.manage')) {
                $nav[] = ['label' => 'Departments', 'route' => 'erp.departments.index', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>'];
            }
        }

        // Documents — shown when the user may read documents
        if ($user->hasPermission('document.read')) {
            $nav[] = ['heading' => 'Vault'];
            $nav[] = ['label' => 'Document Vault', 'route' => 'erp.documents.index', 'routes' => ['erp.documents.index', 'erp.documents.show', 'erp.documents.create'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>'];
        }

        if ($isHr || $isSuper) {
            $nav[] = ['heading' => 'HR'];
            $nav[] = ['label' => 'Employees', 'route' => 'erp.hr.index', 'routes' => ['erp.hr.index', 'erp.hr.show', 'erp.hr.create', 'erp.hr.edit'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'];
            $nav[] = ['label' => 'Attendance', 'route' => 'erp.hr.attendance', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'];
            $nav[] = ['label' => 'Leave Management', 'route' => 'erp.hr.leave', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>'];
            $nav[] = ['label' => 'Training', 'route' => 'erp.hr.training', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>'];
            $nav[] = ['label' => 'SOPs', 'route' => 'erp.hr.sops', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>'];
            $nav[] = ['label' => 'Payroll', 'route' => 'erp.hr.payroll', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'];
        }

        if ($isSuper || $user->hasAnyRole(['hr', 'finance', 'csr_manager'])) {
            $nav[] = ['heading' => 'Reporting'];
            $nav[] = ['label' => 'KPI & Performance', 'route' => 'erp.kpi.index', 'routes' => ['erp.kpi.index', 'erp.kpi.definitions'], 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>'];
        }

        if ($isFinance || $isSuper) {
            $nav[] = ['heading' => 'Email Engine'];
            $nav[] = ['label' => 'Templates', 'route' => 'erp.email.templates', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>'];
            $nav[] = ['label' => 'Email Logs', 'route' => 'erp.email.logs', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>'];
            $nav[] = ['label' => 'Email Queue', 'route' => 'erp.email.queue', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'];
        }

        $nav[] = ['heading' => 'Account'];
        $nav[] = ['label' => 'My Notifications', 'route' => 'erp.notifications', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>'];
        $nav[] = ['label' => 'Active Sessions', 'route' => 'erp.sessions', 'icon' => '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>'];

        return $nav;
    }
}
