<?php

namespace Database\Seeders;

use App\Models\EmailTemplate;
use App\Models\KpiDefinition;
use App\Models\LedgerAccount;
use Illuminate\Database\Seeder;

class ErpSettingSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Ledger accounts ----
        foreach (config('erp.ledger_accounts', []) as $account) {
            LedgerAccount::firstOrCreate(['code' => $account['code']], $account);
        }

        // ---- KPI definitions ----
        $kpis = [
            ['slug' => 'tasks_assigned', 'name' => 'Tasks Assigned', 'unit' => 'count', 'calculation_method' => 'auto', 'frequency' => 'monthly'],
            ['slug' => 'tasks_completed', 'name' => 'Tasks Completed', 'unit' => 'count', 'calculation_method' => 'auto', 'frequency' => 'monthly'],
            ['slug' => 'tasks_overdue', 'name' => 'Overdue Tasks', 'unit' => 'count', 'calculation_method' => 'auto', 'frequency' => 'monthly'],
            ['slug' => 'completion_rate', 'name' => 'Completion Rate', 'unit' => '%', 'calculation_method' => 'auto', 'frequency' => 'monthly'],
            ['slug' => 'avg_completion_days', 'name' => 'Average Completion Time', 'unit' => 'days', 'calculation_method' => 'auto', 'frequency' => 'monthly'],
            ['slug' => 'client_count', 'name' => 'Clients Onboarded', 'unit' => 'count', 'calculation_method' => 'auto', 'frequency' => 'monthly'],
        ];

        foreach ($kpis as $kpi) {
            KpiDefinition::firstOrCreate(['slug' => $kpi['slug']], $kpi);
        }

        // ---- Email templates ----
        $templates = [
            [
                'slug' => 'QUOTATION_SENT',
                'name' => 'Quotation Sent',
                'subject' => 'Your quotation {{quotation_number}} from ARS ERP',
                'body' => "Dear {{client_name}},\n\nThank you for choosing ARS ERP. Please find your quotation {{quotation_number}} for {{total}} {{currency}}.\n\nThis quotation is valid until {{valid_until}}.\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'INVOICE_SENT',
                'name' => 'Invoice Sent',
                'subject' => 'Invoice {{invoice_number}} from ARS ERP',
                'body' => "Dear {{client_name}},\n\nPlease find attached invoice {{invoice_number}} for {{total}} {{currency}}, due on {{due_date}}.\n\nThank you for your business.\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'PAYMENT_RECEIVED',
                'name' => 'Payment Received',
                'subject' => 'Payment received — thank you',
                'body' => "Dear {{client_name}},\n\nWe have received your payment of {{amount}} {{currency}} (ref {{reference}}).\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'TASK_ASSIGNED',
                'name' => 'Task Assigned',
                'subject' => 'New task assigned: {{task_title}}',
                'body' => "Hi {{assignee_name}},\n\nYou have been assigned task {{task_number}}: {{task_title}}.\nDue date: {{due_date}}\nPriority: {{priority}}\n\nPlease log in to the ERP to view details.",
            ],
            [
                'slug' => 'TASK_DEADLINE_WARNING',
                'name' => 'Deadline Reminder',
                'subject' => 'Reminder: Task {{task_number}} is due soon',
                'body' => "Hi {{assignee_name}},\n\nThis is a reminder that task {{task_number}} ({{task_title}}) is due on {{due_date}}.\n\nPlease ensure it is completed on time.",
            ],
            [
                'slug' => 'COMPLIANCE_30_DAY',
                'name' => 'Compliance Renewal Reminder',
                'subject' => 'Your {{document_type}} is expiring soon',
                'body' => "Dear {{client_name}},\n\nYour {{document_type}} ({{reference}}) will expire on {{expiry_date}}.\n\nPlease contact us to arrange renewal.\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'COMPLIANCE_DUE',
                'name' => 'Compliance Due Alert',
                'subject' => 'Your {{document_type}} expires today',
                'body' => "Dear {{client_name}},\n\nYour {{document_type}} ({{reference}}) expires today, {{expiry_date}}. Immediate renewal is recommended.\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'RENEWAL_INVOICE',
                'name' => 'Renewal Invoice',
                'subject' => 'Invoice {{invoice_number}} — Renewal of {{document_type}}',
                'body' => "Dear {{client_name}},\n\nPlease find attached the renewal invoice {{invoice_number}} for your {{document_type}}.\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'QUOTATION_STATUS_CHANGED',
                'name' => 'Quotation Status Changed',
                'subject' => 'Quotation {{quotation_number}} is now {{status}}',
                'body' => "Dear {{client_name}},\n\nYour quotation {{quotation_number}} ({{total}} {{currency}}) is now: {{status}}.\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'INVOICE_STATUS_CHANGED',
                'name' => 'Invoice Status Changed',
                'subject' => 'Invoice {{invoice_number}} is now {{status}}',
                'body' => "Dear {{client_name}},\n\nYour invoice {{invoice_number}} ({{total}} {{currency}}) is now: {{status}}.\nBalance: {{balance}} {{currency}} · Due: {{due_date}}.\n\nBest regards,\nARS ERP Team",
            ],
            [
                'slug' => 'LEAD_LOST',
                'name' => 'Lead Marked Lost',
                'subject' => 'Lead lost: {{lead_name}} ({{lost_reason}})',
                'body' => "Hi,\n\nThe lead {{lead_name}} has been marked as lost.\n\nReason: {{lost_reason}}\nNote: {{lost_note}}\nAssigned to: {{assigned_to}}\nDepartment: {{department}}\n\nThis is an automated notice to admins.",
            ],
        ];

        foreach ($templates as $template) {
            EmailTemplate::firstOrCreate(['slug' => $template['slug']], $template);
        }
    }
}
