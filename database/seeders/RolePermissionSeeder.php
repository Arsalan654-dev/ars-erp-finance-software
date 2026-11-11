<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Define all permissions grouped by module ----
        $permissions = [
            'CRM' => [
                ['client.create', 'Create clients'],
                ['client.read', 'View clients'],
                ['client.update', 'Edit clients'],
                ['client.delete', 'Delete clients'],
                ['client.export', 'Export clients'],
                ['sensitive.view', 'View unmasked sensitive data'],
            ],
            'Quotations' => [
                ['quotation.create', 'Create quotations'],
                ['quotation.read', 'View quotations'],
                ['quotation.update', 'Edit quotations'],
                ['quotation.send', 'Send quotations'],
                ['quotation.convert', 'Convert quotations to invoices'],
                ['quotation.delete', 'Delete quotations'],
            ],
            'Invoices' => [
                ['invoice.create', 'Create invoices'],
                ['invoice.read', 'View invoices'],
                ['invoice.update', 'Edit invoices'],
                ['invoice.lock', 'Lock invoices'],
                ['invoice.send', 'Send invoices'],
                ['invoice.cancel', 'Cancel invoices'],
            ],
            'Finance' => [
                ['payment.create', 'Record payments'],
                ['payment.verify', 'Verify payments'],
                ['payment.read', 'View payments'],
                ['expense.create', 'Record expenses'],
                ['expense.approve', 'Approve expenses'],
                ['expense.read', 'View expenses'],
                ['ledger.read', 'View ledger'],
                ['ledger.post', 'Post ledger entries'],
                ['report.read', 'View financial reports'],
            ],
            'Operations' => [
                ['task.create', 'Create tasks'],
                ['task.read', 'View tasks'],
                ['task.update', 'Update tasks'],
                ['task.assign', 'Assign tasks'],
                ['task.escalate', 'Escalate tasks'],
                ['task.delete', 'Delete tasks'],
            ],
            'Compliance' => [
                ['compliance.create', 'Create compliance records'],
                ['compliance.read', 'View compliance records'],
                ['compliance.update', 'Update compliance records'],
                ['compliance.renew', 'Renew compliance records'],
                ['compliance.delete', 'Delete compliance records'],
            ],
            'Documents' => [
                ['document.upload', 'Upload documents'],
                ['document.read', 'View documents'],
                ['document.download', 'Download documents'],
                ['document.delete', 'Delete documents'],
            ],
            'HR' => [
                ['employee.create', 'Create employees'],
                ['employee.read', 'View employees'],
                ['employee.update', 'Edit employees'],
                ['attendance.manage', 'Manage attendance'],
                ['payroll.manage', 'Manage payroll'],
                ['training.manage', 'Manage training'],
                ['sop.manage', 'Manage SOPs'],
            ],
            'KPI' => [
                ['kpi.read', 'View KPIs'],
                ['kpi.manage', 'Manage KPI definitions'],
                ['kpi.recalculate', 'Recalculate KPIs'],
            ],
            'Email' => [
                ['email.templates', 'Manage email templates'],
                ['email.logs', 'View email logs'],
            ],
            'Admin' => [
                ['user.manage', 'Manage users'],
                ['role.manage', 'Manage roles & permissions'],
                ['audit.read', 'View audit logs'],
                ['settings.manage', 'Manage system settings'],
            ],
            'Leads' => [
                ['lead.create', 'Create leads'],
                ['lead.read', 'View leads'],
                ['lead.update', 'Edit leads'],
                ['lead.delete', 'Delete leads'],
                ['lead.convert', 'Convert leads to clients'],
            ],
            'Projects' => [
                ['project.create', 'Create projects'],
                ['project.read', 'View projects'],
                ['project.update', 'Edit projects'],
                ['project.assign', 'Assign project members'],
                ['project.delete', 'Delete projects'],
            ],
            'Finance Approval' => [
                ['invoice.approve', 'Approve/reject invoices'],
            ],
            'Departments' => [
                ['department.manage', 'Manage departments'],
            ],
        ];

        $created = [];
        foreach ($permissions as $group => $items) {
            foreach ($items as [$slug, $name]) {
                $created[$slug] = Permission::firstOrCreate(
                    ['slug' => $slug],
                    ['name' => $name, 'group' => $group]
                )->id;
            }
        }

        // ---- Define roles and their permission sets ----
        $roles = [
            'super_admin' => array_keys($created),
            'hr' => [
                'client.create', 'client.read', 'client.update',
                'employee.create', 'employee.read', 'employee.update',
                'attendance.manage', 'payroll.manage', 'training.manage', 'sop.manage',
                'kpi.read', 'kpi.manage', 'kpi.recalculate',
                'email.templates', 'email.logs',
                'document.upload', 'document.read', 'document.download',
                'lead.create', 'lead.read', 'lead.update',
                'project.create', 'project.read', 'project.update', 'project.assign',
                'department.manage',
            ],
            'finance' => [
                'client.read', 'client.export',
                'quotation.create', 'quotation.read', 'quotation.update', 'quotation.send', 'quotation.convert',
                'invoice.create', 'invoice.read', 'invoice.update', 'invoice.lock', 'invoice.send', 'invoice.cancel',
                'invoice.approve',
                'payment.create', 'payment.verify', 'payment.read',
                'expense.create', 'expense.approve', 'expense.read',
                'ledger.read', 'ledger.post', 'report.read',
                'document.upload', 'document.read', 'document.download',
                'kpi.read',
                'lead.create', 'lead.read', 'lead.update', 'lead.convert',
                'project.read',
            ],
            'csr_manager' => [
                'client.create', 'client.read', 'client.update', 'client.delete', 'client.export',
                'quotation.create', 'quotation.read', 'quotation.update', 'quotation.send',
                'invoice.create', 'invoice.read', 'invoice.send',
                'payment.create', 'payment.read',
                'task.create', 'task.read', 'task.update', 'task.assign', 'task.escalate', 'task.delete',
                'compliance.create', 'compliance.read', 'compliance.update', 'compliance.renew',
                'document.upload', 'document.read', 'document.download', 'document.delete',
                'kpi.read',
                'lead.create', 'lead.read', 'lead.update', 'lead.convert', 'lead.delete',
                'project.create', 'project.read', 'project.update', 'project.assign', 'project.delete',
                'department.manage',
            ],
            'team_associate' => [
                'client.read',
                'task.read', 'task.update', 'task.assign',
                'invoice.read',
                'compliance.read',
                'document.read', 'document.download',
                'kpi.read',
                'lead.read', 'lead.update',
                'project.read',
            ],
        ];

        foreach ($roles as $roleName => $permSlugs) {
            $role = Role::firstOrCreate(['name' => $roleName]);
            $role->permissions()->sync(array_map(fn ($s) => $created[$s], $permSlugs));
        }
    }
}