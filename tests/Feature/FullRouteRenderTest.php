<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\ComplianceRecord;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\KpiDefinition;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Project;
use App\Models\Quotation;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class FullRouteRenderTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private User $admin;
    private User $hrUser;
    private User $financeUser;
    private User $csrUser;
    private User $associate;
    private Client $client;
    private Lead $lead;
    private Project $project;
    private Task $task;
    private Quotation $quotation;
    private Invoice $invoice;
    private ComplianceRecord $compliance;
    private Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();

        $this->admin = $this->createUserWithRole('super_admin');
        $this->hrUser = $this->createUserWithRole('hr');
        $this->financeUser = $this->createUserWithRole('finance');
        $this->csrUser = $this->createUserWithRole('csr_manager');
        $this->associate = $this->createUserWithRole('team_associate');

        $this->client = Client::create([
            'business_name' => 'Test Client Inc',
            'status' => 'active',
            'created_by' => $this->admin->id,
            'email' => 'test@client.com',
            'phone' => '+441234567890',
        ]);

        $this->lead = Lead::create([
            'source' => 'whatsapp', 'business_name' => 'Test Lead',
            'contact_name' => 'John Doe', 'email' => 'john@lead.com',
            'phone' => '+449876543210', 'department' => 'US TAX',
            'status' => 'new', 'priority' => 'medium', 'created_by' => $this->admin->id,
        ]);

        $this->project = Project::create([
            'project_number' => 'PRJ-TEST001', 'client_id' => $this->client->id,
            'title' => 'Test Project', 'department' => 'US TAX',
            'status' => 'pending', 'priority' => 'medium',
            'created_by' => $this->admin->id, 'assigned_by' => $this->admin->id,
        ]);

        $this->task = Task::create([
            'task_number' => 'TSK-TEST001', 'client_id' => $this->client->id,
            'project_id' => $this->project->id, 'title' => 'Test Task',
            'department' => 'US TAX', 'status' => 'pending',
            'priority' => 'medium', 'created_by' => $this->admin->id,
        ]);

        $this->quotation = Quotation::create([
            'quotation_number' => 'QTN-TEST001', 'client_id' => $this->client->id,
            'title' => 'Test Quotation', 'status' => 'draft',
            'total' => 10000, 'subtotal' => 10000, 'currency' => 'PKR',
            'created_by' => $this->admin->id,
        ]);

        $this->invoice = Invoice::create([
            'invoice_number' => 'INV-TEST001', 'client_id' => $this->client->id,
            'status' => 'draft', 'total' => 15000, 'balance' => 15000,
            'subtotal' => 15000, 'currency' => 'PKR', 'created_by' => $this->admin->id,
        ]);

        $this->compliance = ComplianceRecord::create([
            'client_id' => $this->client->id,
            'document_type' => 'tax_return',
            'title' => 'US Tax Filing',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->employee = Employee::create([
            'user_id' => $this->hrUser->id, 'employee_id' => 'EMP-001',
            'first_name' => 'HR', 'last_name' => 'Manager',
            'status' => 'active', 'department' => 'HR',
        ]);

        Department::create(['name' => 'US TAX', 'slug' => 'us-tax', 'is_active' => true]);
        Department::create(['name' => 'UK TAX', 'slug' => 'uk-tax', 'is_active' => true]);
    }

    private function getRoute($route, $params = []): int
    {
        $url = route($route, $params, false);
        return $this->actingAs($this->admin)->get($url)->status();
    }

    // ===== AUTH =====
    public function test_login_page(): void { $this->get('/erp/login')->assertOk(); }
    public function test_login_submit(): void { $this->post('/erp/login', ['email' => 'admin@consultiser.com', 'password' => 'admin123'])->assertRedirect(); }
    public function test_logout(): void { $this->actingAs($this->admin)->post('/erp/logout')->assertRedirect(); }

    // ===== DASHBOARD =====
    public function test_dashboard(): void { $this->assertEquals(200, $this->getRoute('erp.dashboard')); }

    // ===== CRM (CLIENTS) =====
    public function test_clients_index(): void { $this->assertEquals(200, $this->getRoute('erp.clients.index')); }
    public function test_clients_create(): void { $this->assertEquals(200, $this->getRoute('erp.clients.create')); }
    public function test_clients_show(): void { $this->assertEquals(200, $this->getRoute('erp.clients.show', $this->client->id)); }
    public function test_clients_edit(): void { $this->assertEquals(200, $this->getRoute('erp.clients.edit', $this->client->id)); }

    // ===== LEADS =====
    public function test_leads_index(): void { $this->assertEquals(200, $this->getRoute('erp.leads.index')); }
    public function test_leads_create(): void { $this->assertEquals(200, $this->getRoute('erp.leads.create')); }
    public function test_leads_show(): void { $this->assertEquals(200, $this->getRoute('erp.leads.show', $this->lead->id)); }
    public function test_leads_edit(): void { $this->assertEquals(200, $this->getRoute('erp.leads.edit', $this->lead->id)); }

    // ===== PROJECTS =====
    public function test_projects_index(): void { $this->assertEquals(200, $this->getRoute('erp.projects.index')); }
    public function test_projects_create(): void { $this->assertEquals(200, $this->getRoute('erp.projects.create')); }
    public function test_projects_show(): void { $this->assertEquals(200, $this->getRoute('erp.projects.show', $this->project->id)); }
    public function test_projects_edit(): void { $this->assertEquals(200, $this->getRoute('erp.projects.edit', $this->project->id)); }

    // ===== QUOTATIONS =====
    public function test_quotations_index(): void { $this->assertEquals(200, $this->getRoute('erp.quotations.index')); }
    public function test_quotations_create(): void { $this->assertEquals(200, $this->getRoute('erp.quotations.create')); }
    public function test_quotations_show(): void { $this->assertEquals(200, $this->getRoute('erp.quotations.show', $this->quotation->id)); }
    public function test_quotations_edit(): void { $this->assertEquals(200, $this->getRoute('erp.quotations.edit', $this->quotation->id)); }
    public function test_quotations_pdf(): void { $this->assertEquals(200, $this->getRoute('erp.quotations.pdf', $this->quotation->id)); }

    // ===== INVOICES =====
    public function test_invoices_index(): void { $this->assertEquals(200, $this->getRoute('erp.invoices.index')); }
    public function test_invoices_create(): void { $this->assertEquals(200, $this->getRoute('erp.invoices.create')); }
    public function test_invoices_show(): void { $this->assertEquals(200, $this->getRoute('erp.invoices.show', $this->invoice->id)); }
    public function test_invoices_pdf(): void { $this->assertEquals(200, $this->getRoute('erp.invoices.pdf', $this->invoice->id)); }

    // ===== FINANCE =====
    public function test_finance_payments(): void { $this->assertEquals(200, $this->getRoute('erp.finance.payments')); }
    public function test_finance_payments_create(): void { $this->assertEquals(200, $this->getRoute('erp.finance.payments.create')); }
    public function test_finance_expenses(): void { $this->assertEquals(200, $this->getRoute('erp.finance.expenses')); }
    public function test_finance_expenses_create(): void { $this->assertEquals(200, $this->getRoute('erp.finance.expenses.create')); }
    public function test_finance_ledger(): void { $this->assertEquals(200, $this->getRoute('erp.finance.ledger')); }
    public function test_finance_reports(): void { $this->assertEquals(200, $this->getRoute('erp.finance.reports')); }
    public function test_finance_exchange_rates(): void { $this->assertEquals(200, $this->getRoute('erp.finance.exchange-rates')); }
    public function test_finance_bank_verifications(): void { $this->assertEquals(200, $this->getRoute('erp.finance.bank-verifications')); }

    // ===== FINANCE APPROVALS =====
    public function test_finance_approvals_index(): void { $this->assertEquals(200, $this->getRoute('erp.finance-approvals.index')); }
    public function test_finance_approvals_show(): void { $this->assertEquals(200, $this->getRoute('erp.finance-approvals.show', $this->invoice->id)); }

    // ===== TASKS =====
    public function test_tasks_index(): void { $this->assertEquals(200, $this->getRoute('erp.tasks.index')); }
    public function test_tasks_create(): void { $this->assertEquals(200, $this->getRoute('erp.tasks.create')); }
    public function test_tasks_board(): void { $this->assertEquals(200, $this->getRoute('erp.tasks.board')); }
    public function test_tasks_pool(): void { $this->assertEquals(200, $this->getRoute('erp.tasks.pool')); }
    public function test_tasks_show(): void { $this->assertEquals(200, $this->getRoute('erp.tasks.show', $this->task->id)); }

    // ===== COMPLIANCE =====
    public function test_compliance_index(): void { $this->assertEquals(200, $this->getRoute('erp.compliance.index')); }
    public function test_compliance_create(): void { $this->assertEquals(200, $this->getRoute('erp.compliance.create')); }
    public function test_compliance_show(): void { $this->assertEquals(200, $this->getRoute('erp.compliance.show', $this->compliance->id)); }
    public function test_compliance_edit(): void { $this->assertEquals(200, $this->getRoute('erp.compliance.edit', $this->compliance->id)); }

    // ===== DOCUMENTS =====
    public function test_documents_index(): void { $this->assertEquals(200, $this->getRoute('erp.documents.index')); }
    public function test_documents_create(): void { $this->assertEquals(200, $this->getRoute('erp.documents.create')); }

    // ===== HR =====
    public function test_hr_index(): void { $this->assertEquals(200, $this->getRoute('erp.hr.index')); }
    public function test_hr_create(): void { $this->assertEquals(200, $this->getRoute('erp.hr.create')); }
    public function test_hr_show(): void { $this->assertEquals(200, $this->getRoute('erp.hr.show', $this->employee->id)); }
    public function test_hr_edit(): void { $this->assertEquals(200, $this->getRoute('erp.hr.edit', $this->employee->id)); }
    public function test_hr_attendance(): void { $this->assertEquals(200, $this->getRoute('erp.hr.attendance')); }
    public function test_hr_leave(): void { $this->assertEquals(200, $this->getRoute('erp.hr.leave')); }
    public function test_hr_training(): void { $this->assertEquals(200, $this->getRoute('erp.hr.training')); }
    public function test_hr_sops(): void { $this->assertEquals(200, $this->getRoute('erp.hr.sops')); }
    public function test_hr_payroll(): void { $this->assertEquals(200, $this->getRoute('erp.hr.payroll')); }

    // ===== KPI =====
    public function test_kpi_index(): void { $this->assertEquals(200, $this->getRoute('erp.kpi.index')); }
    public function test_kpi_definitions(): void { $this->assertEquals(200, $this->getRoute('erp.kpi.definitions')); }

    // ===== EMAIL =====
    public function test_email_templates(): void { $this->assertEquals(200, $this->getRoute('erp.email.templates')); }
    public function test_email_logs(): void { $this->assertEquals(200, $this->getRoute('erp.email.logs')); }
    public function test_email_queue(): void { $this->assertEquals(200, $this->getRoute('erp.email.queue')); }

    // ===== ADMIN =====
    public function test_admin_users(): void { $this->assertEquals(200, $this->getRoute('erp.admin.users')); }
    public function test_admin_users_create(): void { $this->assertEquals(200, $this->getRoute('erp.admin.users.create')); }
    public function test_admin_users_edit(): void { $this->assertEquals(200, $this->getRoute('erp.admin.users.edit', $this->admin->id)); }
    public function test_admin_roles(): void { $this->assertEquals(200, $this->getRoute('erp.admin.roles')); }
    public function test_admin_audit_logs(): void { $this->assertEquals(200, $this->getRoute('erp.admin.audit-logs')); }
    public function test_admin_settings(): void { $this->assertEquals(200, $this->getRoute('erp.admin.settings')); }

    // ===== DEPARTMENTS =====
    public function test_departments_index(): void { $this->assertEquals(200, $this->getRoute('erp.departments.index')); }

    // ===== ACCOUNT =====
    public function test_notifications(): void { $this->assertEquals(200, $this->getRoute('erp.notifications')); }
    public function test_sessions(): void { $this->assertEquals(200, $this->getRoute('erp.sessions')); }
    public function test_password_change(): void { $this->assertEquals(200, $this->getRoute('erp.password.change')); }
}
