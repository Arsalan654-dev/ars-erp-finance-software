<?php

use App\Http\Controllers\Erp\AdminController;
use App\Http\Controllers\Erp\AuthController;
use App\Http\Controllers\Erp\ClientController;
use App\Http\Controllers\Erp\ComplianceController;
use App\Http\Controllers\Erp\CsrPipelineController;
use App\Http\Controllers\Erp\DashboardController;
use App\Http\Controllers\Erp\DepartmentController;
use App\Http\Controllers\Erp\DocumentController;
use App\Http\Controllers\Erp\EmailController;
use App\Http\Controllers\Erp\EmployeeController;
use App\Http\Controllers\Erp\FinanceApprovalController;
use App\Http\Controllers\Erp\FinanceController;
use App\Http\Controllers\Erp\InvoiceController;
use App\Http\Controllers\Erp\KpiController;
use App\Http\Controllers\Erp\LeadController;
use App\Http\Controllers\Erp\ProjectController;
use App\Http\Controllers\Erp\QuotationController;
use App\Http\Controllers\Erp\SalesPackageController;
use App\Http\Controllers\Erp\TaskController;
use App\Http\Controllers\Erp\WhatsappAccountController;
use App\Http\Controllers\Erp\WhatsAppWebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Consultiser ERP — Web Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return redirect()->route('erp.login');
});

// ---- WhatsApp webhook (public — no auth; verified via token) ----
Route::prefix('erp')->name('erp.')->group(function (): void {
    Route::match(['get', 'post'], '/whatsapp/webhook/{phone_number_id?}', [WhatsAppWebhookController::class, 'webhook'])->name('whatsapp.webhook');
});

// Auth middleware redirect target — Laravel looks for route('login')
Route::get('/erp/login', [AuthController::class, 'showLogin'])->name('login');

// ---- Auth ----
Route::prefix('erp')->name('erp.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/login/2fa', [AuthController::class, 'show2fa'])->name('login.2fa');
    Route::post('/login/2fa', [AuthController::class, 'verify2fa'])->name('login.2fa.submit');

    // Forgot / Reset Password (no auth required)
    Route::get('/password/forgot', [AuthController::class, 'showForgotPassword'])->name('password.forgot');
    Route::post('/password/forgot', [AuthController::class, 'sendResetLink'])->name('password.forgot.submit');
    Route::get('/password/reset/{token}', [AuthController::class, 'showResetForm'])->name('password.reset');
    Route::post('/password/reset', [AuthController::class, 'resetPassword'])->name('password.reset.submit');

    Route::middleware(['auth', 'session.security', 'audit'])->group(function (): void {
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Password
        Route::get('/password/change', [AuthController::class, 'showChangePassword'])->name('password.change');
        Route::post('/password/change', [AuthController::class, 'changePassword'])->name('password.change.submit');

        // Profile
        Route::get('/profile', [AuthController::class, 'showProfile'])->name('profile');
        Route::patch('/profile', [AuthController::class, 'updateProfile'])->name('profile.update');

        // Sessions
        Route::get('/sessions', [AuthController::class, 'sessions'])->name('sessions');
        Route::post('/sessions/logout-other', [AuthController::class, 'logoutOtherSessions'])->name('sessions.logout-other');
        Route::post('/sessions/2fa', [AuthController::class, 'toggle2fa'])->name('sessions.2fa');

        // Notifications
        Route::get('/notifications', [AdminController::class, 'notifications'])->name('notifications');
        Route::post('/notifications/{notification}/read', [AdminController::class, 'markNotification'])->name('notifications.read');
        Route::post('/notifications/read-all', [AdminController::class, 'markAllRead'])->name('notifications.read-all');

        // ---- CRM ----
        Route::get('/clients', [ClientController::class, 'index'])->name('clients.index')->middleware('permission:client.read');
        Route::get('/clients/create', [ClientController::class, 'create'])->name('clients.create')->middleware('permission:client.create');
        Route::post('/clients', [ClientController::class, 'store'])->name('clients.store')->middleware('permission:client.create');
        Route::get('/clients/{client}', [ClientController::class, 'show'])->name('clients.show')->middleware('permission:client.read');
        Route::get('/clients/{client}/edit', [ClientController::class, 'edit'])->name('clients.edit')->middleware('permission:client.update');
        Route::patch('/clients/{client}', [ClientController::class, 'update'])->name('clients.update')->middleware('permission:client.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('clients.destroy')->middleware('permission:client.delete');

        // ---- Client Nested Resources ----
        Route::middleware('permission:client.update')->group(function (): void {
            Route::post('/clients/{client}/contacts', [ClientController::class, 'addContact'])->name('clients.contacts.store');
            Route::delete('/clients/{client}/contacts/{contact}', [ClientController::class, 'deleteContact'])->name('clients.contacts.destroy');
            Route::post('/clients/{client}/services', [ClientController::class, 'addService'])->name('clients.services.store');
            Route::delete('/clients/{client}/services/{service}', [ClientController::class, 'deleteService'])->name('clients.services.destroy');
            Route::post('/clients/{client}/notes', [ClientController::class, 'addNote'])->name('clients.notes.store');
            Route::post('/clients/{client}/whatsapp', [WhatsAppWebhookController::class, 'sendMessage'])->name('clients.whatsapp.send');
            Route::post('/clients/{client}/whatsapp/template', [WhatsAppWebhookController::class, 'sendTemplate'])->name('clients.whatsapp.template');
        });

        // ---- Quotations ----
        Route::get('/quotations', [QuotationController::class, 'index'])->name('quotations.index')->middleware('permission:quotation.read');
        Route::get('/quotations/create', [QuotationController::class, 'create'])->name('quotations.create')->middleware('permission:quotation.create');
        Route::post('/quotations', [QuotationController::class, 'store'])->name('quotations.store')->middleware('permission:quotation.create');
        Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show')->middleware('permission:quotation.read');
        Route::get('/quotations/{quotation}/pdf', [QuotationController::class, 'pdf'])->name('quotations.pdf')->middleware('permission:quotation.read');
        Route::get('/quotations/{quotation}/edit', [QuotationController::class, 'edit'])->name('quotations.edit')->middleware('permission:quotation.update');
        Route::patch('/quotations/{quotation}', [QuotationController::class, 'update'])->name('quotations.update')->middleware('permission:quotation.update');
        Route::post('/quotations/{quotation}/send', [QuotationController::class, 'send'])->name('quotations.send')->middleware('permission:quotation.send');
        Route::post('/quotations/{quotation}/accept', [QuotationController::class, 'accept'])->name('quotations.accept')->middleware('permission:quotation.convert');
        Route::post('/quotations/{quotation}/convert', [QuotationController::class, 'convertToInvoice'])->name('quotations.convert')->middleware('permission:quotation.convert');
        Route::post('/quotations/{quotation}/duplicate', [QuotationController::class, 'duplicate'])->name('quotations.duplicate')->middleware('permission:quotation.create');
        Route::delete('/quotations/{quotation}', [QuotationController::class, 'destroy'])->name('quotations.destroy')->middleware('permission:quotation.delete');

        // ---- Invoices ----
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index')->middleware('permission:invoice.read');
        Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create')->middleware('permission:invoice.create');
        Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store')->middleware('permission:invoice.create');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show')->middleware('permission:invoice.read');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf')->middleware('permission:invoice.read');
        Route::post('/invoices/{invoice}/lock', [InvoiceController::class, 'lock'])->name('invoices.lock')->middleware('permission:invoice.lock');
        Route::post('/invoices/{invoice}/send', [InvoiceController::class, 'send'])->name('invoices.send')->middleware('permission:invoice.send');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])->name('invoices.cancel')->middleware('permission:invoice.cancel');
        Route::post('/invoices/{invoice}/duplicate', [InvoiceController::class, 'duplicate'])->name('invoices.duplicate')->middleware('permission:invoice.create');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])->name('invoices.destroy')->middleware('role:super_admin');

        // ---- Finance (role-gated: super_admin or finance) ----
        Route::middleware('role:super_admin,finance')->group(function (): void {
            Route::get('/finance/payments', [FinanceController::class, 'payments'])->name('finance.payments');
            Route::get('/finance/payments/create', [FinanceController::class, 'createPayment'])->name('finance.payments.create');
            Route::post('/finance/payments', [FinanceController::class, 'storePayment'])->name('finance.payments.store');
            Route::get('/finance/payments/{payment}', [FinanceController::class, 'showPayment'])->name('finance.payments.show');
            Route::post('/finance/payments/{payment}/acknowledge', [FinanceController::class, 'acknowledgePayment'])->name('finance.payments.acknowledge');
            Route::post('/finance/payments/{payment}/verify', [FinanceController::class, 'verifyPayment'])->name('finance.payments.verify');
            Route::post('/finance/payments/{payment}/reject', [FinanceController::class, 'rejectPayment'])->name('finance.payments.reject');
            Route::get('/finance/payments/{payment}/screenshot', [FinanceController::class, 'paymentScreenshot'])->name('finance.payments.screenshot');
            Route::get('/finance/payments/{payment}/receipt', [FinanceController::class, 'paymentReceipt'])->name('finance.payments.receipt');
            Route::post('/finance/payments/{payment}/bank-verification', [FinanceController::class, 'storeBankVerification'])->name('finance.payments.bank-verification');
            Route::delete('/finance/payments/{payment}', [FinanceController::class, 'destroyPayment'])->name('finance.payments.destroy');

            Route::get('/finance/bank-verifications', [FinanceController::class, 'bankVerifications'])->name('finance.bank-verifications');

            Route::get('/finance/expenses', [FinanceController::class, 'expenses'])->name('finance.expenses');
            Route::get('/finance/expenses/create', [FinanceController::class, 'createExpense'])->name('finance.expenses.create');
            Route::post('/finance/expenses', [FinanceController::class, 'storeExpense'])->name('finance.expenses.store');
            Route::post('/finance/expenses/{expense}/approve', [FinanceController::class, 'approveExpense'])->name('finance.expenses.approve');

            Route::get('/finance/ledger', [FinanceController::class, 'ledger'])->name('finance.ledger');
            Route::get('/finance/reports', [FinanceController::class, 'reports'])->name('finance.reports');
            Route::get('/finance/exchange-rates', [FinanceController::class, 'exchangeRates'])->name('finance.exchange-rates');
            Route::post('/finance/exchange-rates', [FinanceController::class, 'storeExchangeRate'])->name('finance.exchange-rates.store');
            Route::delete('/finance/exchange-rates/{exchangeRate}', [FinanceController::class, 'destroyExchangeRate'])->name('finance.exchange-rates.destroy');
        });

        // ---- Tasks ----
        Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index')->middleware('permission:task.read');
        Route::get('/tasks/board', [TaskController::class, 'board'])->name('tasks.board')->middleware('permission:task.read');
        Route::get('/tasks/pool', [TaskController::class, 'pool'])->name('tasks.pool')->middleware('permission:task.read');
        Route::get('/tasks/create', [TaskController::class, 'create'])->name('tasks.create')->middleware('permission:task.create');
        Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store')->middleware('permission:task.create');
        Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show')->middleware('permission:task.read');
        Route::post('/tasks/{task}/claim', [TaskController::class, 'claim'])->name('tasks.claim')->middleware('permission:task.assign');
        Route::post('/tasks/{task}/status', [TaskController::class, 'updateStatus'])->name('tasks.status')->middleware('permission:task.update');
        Route::post('/tasks/{task}/assign', [TaskController::class, 'assign'])->name('tasks.assign')->middleware('permission:task.assign');
        Route::post('/tasks/{task}/comments', [TaskController::class, 'comment'])->name('tasks.comments')->middleware('permission:task.update');
        Route::post('/tasks/{task}/escalate', [TaskController::class, 'escalate'])->name('tasks.escalate')->middleware('permission:task.escalate');
        Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy')->middleware('permission:task.delete');

        // ---- Compliance ----
        Route::middleware('role:super_admin,csr_manager,finance')->group(function (): void {
            Route::get('/compliance', [ComplianceController::class, 'index'])->name('compliance.index');
            Route::get('/compliance/create', [ComplianceController::class, 'create'])->name('compliance.create');
            Route::post('/compliance', [ComplianceController::class, 'store'])->name('compliance.store');
            Route::get('/compliance/{record}', [ComplianceController::class, 'show'])->name('compliance.show');
            Route::get('/compliance/{record}/edit', [ComplianceController::class, 'edit'])->name('compliance.edit');
            Route::patch('/compliance/{record}', [ComplianceController::class, 'update'])->name('compliance.update');
            Route::post('/compliance/{record}/renew', [ComplianceController::class, 'renew'])->name('compliance.renew');
            Route::delete('/compliance/{record}', [ComplianceController::class, 'destroy'])->name('compliance.destroy');
        });

        // ---- Documents ----
        Route::get('/documents', [DocumentController::class, 'index'])->name('documents.index')->middleware('permission:document.read');
        Route::get('/documents/create', [DocumentController::class, 'create'])->name('documents.create')->middleware('permission:document.upload');
        Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store')->middleware('permission:document.upload');
        Route::get('/documents/{document}', [DocumentController::class, 'show'])->name('documents.show')->middleware('permission:document.read');
        Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download')->middleware('permission:document.download');
        Route::get('/documents/{document}/access-history', [DocumentController::class, 'accessHistory'])->name('documents.access-history')->middleware('permission:document.read');
        Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy')->middleware('permission:document.delete');

        // ---- HR (static sub-routes must precede the {employee} wildcard) ----
        Route::get('/hr', [EmployeeController::class, 'index'])->name('hr.index')->middleware('role:super_admin,hr');
        Route::get('/hr/create', [EmployeeController::class, 'create'])->name('hr.create')->middleware('role:super_admin,hr');
        Route::post('/hr', [EmployeeController::class, 'store'])->name('hr.store')->middleware('role:super_admin,hr');

        Route::get('/hr/attendance', [EmployeeController::class, 'attendance'])->name('hr.attendance')->middleware('role:super_admin,hr');
        Route::post('/hr/attendance', [EmployeeController::class, 'markAttendance'])->name('hr.attendance.store')->middleware('role:super_admin,hr');

        Route::get('/hr/training', [EmployeeController::class, 'training'])->name('hr.training')->middleware('role:super_admin,hr');
        Route::post('/hr/training', [EmployeeController::class, 'storeTraining'])->name('hr.training.store')->middleware('role:super_admin,hr');
        Route::post('/hr/training/assign', [EmployeeController::class, 'assignTraining'])->name('hr.training.assign')->middleware('role:super_admin,hr');
        Route::post('/hr/training/{assignment}/complete', [EmployeeController::class, 'completeTraining'])->name('hr.training.complete')->middleware('role:super_admin,hr');

        Route::get('/hr/sops', [EmployeeController::class, 'sops'])->name('hr.sops')->middleware('role:super_admin,hr');
        Route::post('/hr/sops', [EmployeeController::class, 'storeSop'])->name('hr.sops.store')->middleware('role:super_admin,hr');

        Route::get('/hr/payroll', [EmployeeController::class, 'payroll'])->name('hr.payroll')->middleware('role:super_admin,hr');
        Route::post('/hr/payroll', [EmployeeController::class, 'storePayroll'])->name('hr.payroll.store')->middleware('role:super_admin,hr');
        Route::post('/hr/payroll/{payroll}/approve', [EmployeeController::class, 'approvePayroll'])->name('hr.payroll.approve')->middleware('role:super_admin,hr');
        Route::get('/hr/payroll/{payroll}/payslip', [EmployeeController::class, 'payslip'])->name('hr.payroll.payslip')->middleware('role:super_admin,hr');

        // ---- Leave Management (must precede {employee} wildcard) ----
        Route::get('/hr/leave', [EmployeeController::class, 'leave'])->name('hr.leave')->middleware('role:super_admin,hr');
        Route::post('/hr/leave', [EmployeeController::class, 'storeLeave'])->name('hr.leave.store')->middleware('role:super_admin,hr');
        Route::post('/hr/leave/{leave}/decide', [EmployeeController::class, 'decideLeave'])->name('hr.leave.decide')->middleware('role:super_admin,hr');

        Route::get('/hr/{employee}', [EmployeeController::class, 'show'])->name('hr.show')->middleware('role:super_admin,hr');
        Route::get('/hr/{employee}/edit', [EmployeeController::class, 'edit'])->name('hr.edit')->middleware('role:super_admin,hr');
        Route::patch('/hr/{employee}', [EmployeeController::class, 'update'])->name('hr.update')->middleware('role:super_admin,hr');

        // ---- KPI ----
        Route::middleware('role:super_admin,csr_manager')->group(function (): void {
            Route::get('/kpi', [KpiController::class, 'index'])->name('kpi.index');
            Route::get('/kpi/definitions', [KpiController::class, 'definitions'])->name('kpi.definitions');
            Route::post('/kpi/definitions', [KpiController::class, 'storeDefinition'])->name('kpi.definitions.store')->middleware('role:super_admin');
            Route::post('/kpi/recalculate', [KpiController::class, 'recalculate'])->name('kpi.recalculate')->middleware('role:super_admin');
        });

        // ---- Email Engine ----
        Route::middleware('role:super_admin,csr_manager')->group(function (): void {
            Route::get('/email/templates', [EmailController::class, 'templates'])->name('email.templates');
            Route::post('/email/templates', [EmailController::class, 'storeTemplate'])->name('email.templates.store');
            Route::patch('/email/templates/{template}', [EmailController::class, 'updateTemplate'])->name('email.templates.update');
            Route::get('/email/logs', [EmailController::class, 'logs'])->name('email.logs');
            Route::get('/email/queue', [EmailController::class, 'queue'])->name('email.queue');
            Route::post('/email/queue/process', [EmailController::class, 'processQueue'])->name('email.queue.process');
        });

        // ---- Leads ----
        Route::get('/leads', [LeadController::class, 'index'])->name('leads.index')->middleware('permission:client.read');
        Route::get('/leads/create', [LeadController::class, 'create'])->name('leads.create')->middleware('permission:client.create');
        Route::get('/leads/pipeline', [CsrPipelineController::class, 'index'])->name('leads.pipeline')->middleware('permission:lead.read');
        Route::post('/leads', [LeadController::class, 'store'])->name('leads.store')->middleware('permission:client.create');
        Route::get('/leads/{lead}', [LeadController::class, 'show'])->name('leads.show')->middleware('permission:client.read');
        Route::get('/leads/{lead}/payment-screenshot', [LeadController::class, 'paymentScreenshot'])->name('leads.payment-screenshot')->middleware('permission:client.read');
        Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->name('leads.edit')->middleware('permission:client.update');
        Route::patch('/leads/{lead}', [LeadController::class, 'update'])->name('leads.update')->middleware('permission:client.update');
        Route::post('/leads/{lead}/transition', [LeadController::class, 'transition'])->name('leads.transition')->middleware('permission:client.update');
        Route::post('/leads/{lead}/convert', [LeadController::class, 'convertToClient'])->name('leads.convert')->middleware('permission:client.create');
        Route::post('/leads/{lead}/activity', [LeadController::class, 'storeActivity'])->name('leads.activity.store')->middleware('permission:client.update');
        Route::delete('/leads/{lead}', [LeadController::class, 'destroy'])->name('leads.destroy')->middleware('permission:lead.delete');

        // ---- Sales Packages (quotation/invoice templates) ----
        Route::middleware('role:super_admin,csr_manager')->group(function (): void {
            Route::get('/sales-packages', [SalesPackageController::class, 'index'])->name('sales-packages.index');
            Route::get('/sales-packages/create', [SalesPackageController::class, 'create'])->name('sales-packages.create');
            Route::post('/sales-packages', [SalesPackageController::class, 'store'])->name('sales-packages.store');
            Route::get('/sales-packages/{salesPackage}/edit', [SalesPackageController::class, 'edit'])->name('sales-packages.edit');
            Route::patch('/sales-packages/{salesPackage}', [SalesPackageController::class, 'update'])->name('sales-packages.update');
            Route::delete('/sales-packages/{salesPackage}', [SalesPackageController::class, 'destroy'])->name('sales-packages.destroy');
            Route::post('/sales-packages/{salesPackage}/toggle', [SalesPackageController::class, 'toggle'])->name('sales-packages.toggle');
        });

        // ---- Projects ----
        Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index')->middleware('permission:task.read');
        Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create')->middleware('permission:task.create');
        Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store')->middleware('permission:task.create');
        Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show')->middleware('permission:task.read');
        Route::get('/projects/{project}/edit', [ProjectController::class, 'edit'])->name('projects.edit')->middleware('permission:task.update');
        Route::patch('/projects/{project}', [ProjectController::class, 'update'])->name('projects.update')->middleware('permission:task.update');
        Route::post('/projects/{project}/transition', [ProjectController::class, 'transition'])->name('projects.transition')->middleware('permission:task.update');
        Route::post('/projects/{project}/members', [ProjectController::class, 'addMember'])->name('projects.members.store')->middleware('permission:task.assign');
        Route::delete('/projects/{project}/members/{member}', [ProjectController::class, 'removeMember'])->name('projects.members.destroy')->middleware('permission:task.assign');

        // ---- Departments (Super Admin or CSR Manager) ----
        Route::middleware('role:super_admin,csr_manager')->group(function (): void {
            Route::get('/departments', [DepartmentController::class, 'index'])->name('departments.index');
            Route::post('/departments', [DepartmentController::class, 'store'])->name('departments.store');
            Route::patch('/departments/{department}', [DepartmentController::class, 'update'])->name('departments.update');
            Route::post('/departments/{department}/members', [DepartmentController::class, 'addMember'])->name('departments.members.store');
            Route::delete('/departments/{department}/members/{user}', [DepartmentController::class, 'removeMember'])->name('departments.members.destroy');
        });

        // ---- Finance Approvals (role-gated: super_admin or finance) ----
        Route::middleware('role:super_admin,finance')->group(function (): void {
            Route::get('/finance/approvals', [FinanceApprovalController::class, 'index'])->name('finance-approvals.index');
            Route::get('/finance/approvals/{invoice}', [FinanceApprovalController::class, 'show'])->name('finance-approvals.show');
            Route::post('/finance/approvals/{invoice}/approve', [FinanceApprovalController::class, 'approve'])->name('finance-approvals.approve');
            Route::post('/finance/approvals/{invoice}/reject', [FinanceApprovalController::class, 'reject'])->name('finance-approvals.reject');
        });

        // ---- Admin (Super Admin only) ----
        Route::middleware('role:super_admin')->group(function (): void {
            Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
            Route::get('/admin/users/create', [AdminController::class, 'createUser'])->name('admin.users.create');
            Route::post('/admin/users', [AdminController::class, 'storeUser'])->name('admin.users.store');
            Route::get('/admin/users/{user}/edit', [AdminController::class, 'editUser'])->name('admin.users.edit');
            Route::patch('/admin/users/{user}', [AdminController::class, 'updateUser'])->name('admin.users.update');
            Route::post('/admin/users/{user}/permissions', [AdminController::class, 'updateUserPermissions'])->name('admin.users.permissions.update');

            Route::get('/admin/roles', [AdminController::class, 'roles'])->name('admin.roles');
            Route::post('/admin/roles', [AdminController::class, 'storeRole'])->name('admin.roles.store');
            Route::patch('/admin/roles/{role}', [AdminController::class, 'updateRole'])->name('admin.roles.update');
            Route::delete('/admin/roles/{role}', [AdminController::class, 'destroyRole'])->name('admin.roles.destroy');

            Route::get('/admin/audit-logs', [AdminController::class, 'auditLogs'])->name('admin.audit-logs');
            Route::get('/admin/reports', [AdminController::class, 'reports'])->name('admin.reports');
            Route::get('/admin/work-report', [AdminController::class, 'workReport'])->name('admin.work-report');
            Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
            Route::post('/admin/settings', [AdminController::class, 'updateSettings'])->name('admin.settings.update');

            Route::get('/admin/email-settings', [AdminController::class, 'emailSettings'])->name('admin.email-settings');
            Route::post('/admin/email-settings', [AdminController::class, 'updateEmailSettings'])->name('admin.email-settings.update');
            Route::post('/admin/email-settings/test', [AdminController::class, 'testEmailSettings'])->name('admin.email-settings.test');

            Route::get('/admin/whatsapp', [WhatsappAccountController::class, 'index'])->name('admin.whatsapp.index');
            Route::get('/admin/whatsapp/create', [WhatsappAccountController::class, 'create'])->name('admin.whatsapp.create');
            Route::post('/admin/whatsapp', [WhatsappAccountController::class, 'store'])->name('admin.whatsapp.store');
            Route::get('/admin/whatsapp/{account}/edit', [WhatsappAccountController::class, 'edit'])->name('admin.whatsapp.edit');
            Route::patch('/admin/whatsapp/{account}', [WhatsappAccountController::class, 'update'])->name('admin.whatsapp.update');
            Route::delete('/admin/whatsapp/{account}', [WhatsappAccountController::class, 'destroy'])->name('admin.whatsapp.destroy');
        });
    });
});
