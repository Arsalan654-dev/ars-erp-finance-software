<?php

return [

    'app_name' => env('APP_NAME', 'Consultiser ERP'),

    /*
    |--------------------------------------------------------------------------
    | Session security
    |--------------------------------------------------------------------------
    */
    'session_idle_timeout' => env('ERP_SESSION_IDLE_TIMEOUT', 30), // minutes

    /*
    |--------------------------------------------------------------------------
    | Default exchange rates (manual fallback when no rate is stored)
    |--------------------------------------------------------------------------
    */
    'default_exchange_rates' => [
        'USD' => 280.0,
        'GBP' => 355.0,
        'EUR' => 305.0,
    ],

    /*
    |--------------------------------------------------------------------------
    | Supported currencies
    |--------------------------------------------------------------------------
    */
    'currencies' => ['PKR', 'USD', 'GBP', 'EUR'],

    /*
    |--------------------------------------------------------------------------
    | Lead / project departments
    |--------------------------------------------------------------------------
    */
    'departments' => ['US TAX', 'UK TAX', 'LEGAL', 'Legal- US', 'Legal- UK', 'GENERAL'],

    /*
    |--------------------------------------------------------------------------
    | Lead "Lost" reason template (fixed list + free-text special note)
    |--------------------------------------------------------------------------
    */
    'lead_lost_reasons' => [
        'Budget Issue',
        'Already Using Another Vendor',
        'No Response / Unreachable',
        'Wrong Contact / Invalid Lead',
        'Requirement Postponed',
        'Duplicate Lead',
        'Ineligible for Service',
        'Documentation Issue',
        'Moved to Another Provider',
        'Client Self-Filed / DIY',
    ],

    /*
    |--------------------------------------------------------------------------
    | Lead sources (where a lead came from)
    |--------------------------------------------------------------------------
    */
    'lead_sources' => ['whatsapp', 'website', 'referral', 'manual', 'phone', 'facebook', 'linkedin'],

    /*
    |--------------------------------------------------------------------------
    | Lead statuses (pipeline stages)
    |--------------------------------------------------------------------------
    */
    'lead_statuses' => ['new', 'contacted', 'follow_up', 'qualified', 'quotation', 'converted', 'lost'],

    /*
    |--------------------------------------------------------------------------
    | Task statuses
    |--------------------------------------------------------------------------
    */
    'task_statuses' => ['pending', 'assigned', 'in_progress', 'review', 'completed', 'cancelled'],

    /*
    |--------------------------------------------------------------------------
    | Task escalation schedule (days overdue -> escalation level)
    |--------------------------------------------------------------------------
    */
    'task_escalation' => [
        1 => 1, // 1 day overdue -> level 1
        3 => 2, // 3 days overdue -> level 2
        5 => 3, // 5 days overdue -> level 3
    ],

    /*
    |--------------------------------------------------------------------------
    | Compliance renewal schedule (days before expiry)
    |--------------------------------------------------------------------------
    */
    'compliance_reminders' => [
        'internal_flag_days' => 60,
        'client_reminder_days' => 30,
        'due_alert_days' => 0,
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp integration
    |--------------------------------------------------------------------------
    */
    'whatsapp' => [
        'enabled' => env('WHATSAPP_ENABLED', false),
        'verify_token' => env('WHATSAPP_VERIFY_TOKEN', 'consultiser-verify'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ledger account seeds
    |--------------------------------------------------------------------------
    */
    'ledger_accounts' => [
        ['code' => '1000', 'name' => 'Cash & Bank', 'type' => 'asset'],
        ['code' => '1200', 'name' => 'Cash/Bank', 'type' => 'asset'],
        ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset'],
        ['code' => '4000', 'name' => 'Consulting Revenue', 'type' => 'income'],
        ['code' => '4100', 'name' => 'Tax Services Revenue', 'type' => 'income'],
        ['code' => '4200', 'name' => 'Legal Services Revenue', 'type' => 'income'],
        ['code' => '5000', 'name' => 'Operating Expenses', 'type' => 'expense'],
        ['code' => '5100', 'name' => 'Payroll Expense', 'type' => 'expense'],
        ['code' => '2000', 'name' => 'Accounts Payable', 'type' => 'liability'],
    ],
];
