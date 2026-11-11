<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\Project;
use App\Models\Client;
use App\Models\Department;
use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\InteractsWithRoles;

class LeadProjectTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private User $admin;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->admin = $this->createUserWithRole('super_admin');
        $this->client = Client::create([
            'business_name' => 'Test Client',
            'status' => 'active',
            'created_by' => $this->admin->id,
            'email' => 'test@test.com',
        ]);
    }

    // ===== LEAD TESTS =====

    public function test_lead_index_accessible(): void
    {
        $this->actingAs($this->admin)->get(route('erp.leads.index'))->assertOk();
    }

    public function test_lead_create_form(): void
    {
        $this->actingAs($this->admin)->get(route('erp.leads.create'))->assertOk();
    }

    public function test_lead_store(): void
    {
        $this->actingAs($this->admin)->post(route('erp.leads.store'), [
            'business_name' => 'Test Corp',
            'contact_name' => 'John',
            'email' => 'john@test.com',
            'phone' => '+44123456789',
            'source' => 'whatsapp',
            'department' => 'US TAX',
            'priority' => 'high',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['business_name' => 'Test Corp', 'status' => 'new']);
    }

    public function test_lead_show(): void
    {
        $lead = Lead::create([
            'source' => 'whatsapp',
            'business_name' => 'Show Me',
            'status' => 'new',
            'priority' => 'medium',
            'department' => 'GENERAL',
            'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->get(route('erp.leads.show', $lead))->assertOk();
    }

    public function test_lead_update(): void
    {
        $lead = Lead::create([
            'source' => 'whatsapp',
            'business_name' => 'Old Name',
            'status' => 'new',
            'priority' => 'medium',
            'department' => 'GENERAL',
            'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->patch(route('erp.leads.update', $lead), [
            'business_name' => 'Updated Corp',
            'source' => 'website',
            'department' => 'LEGAL',
            'priority' => 'urgent',
        ])->assertRedirect();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'business_name' => 'Updated Corp']);
    }

    public function test_lead_transition_contacted(): void
    {
        $lead = Lead::create([
            'source' => 'whatsapp', 'status' => 'new', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->post(route('erp.leads.transition', $lead), ['status' => 'contacted'])->assertRedirect();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'contacted']);
    }

    public function test_lead_invalid_transition_rejected(): void
    {
        $lead = Lead::create([
            'source' => 'whatsapp', 'status' => 'new', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->post(route('erp.leads.transition', $lead), ['status' => 'converted'])
            ->assertSessionHas('error');
    }

    public function test_lead_convert_to_client(): void
    {
        $lead = Lead::create([
            'source' => 'whatsapp', 'status' => 'qualified', 'priority' => 'medium',
            'department' => 'GENERAL', 'business_name' => 'Convert Me', 'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->post(route('erp.leads.convert', $lead), [
            'payment_type' => 'full',
            'payment_currency' => 'USD',
            'payment_amount' => 500,
            'payment_method' => 'bank',
            'payment_date' => now()->toDateString(),
            'payment_screenshot' => \Illuminate\Http\UploadedFile::fake()->create('receipt.png', 100, 'image/png'),
            'screenshot_amount' => 500,
        ])->assertRedirect();
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'converted']);
        $this->assertDatabaseHas('clients', ['business_name' => 'Convert Me']);
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'payment_type' => 'full']);
        $this->assertDatabaseHas('payments', ['lead_id' => $lead->id, 'status' => 'pending', 'amount' => 500]);
    }

    public function test_lead_convert_rejected_if_not_qualified(): void
    {
        $lead = Lead::create([
            'source' => 'whatsapp', 'status' => 'new', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->post(route('erp.leads.convert', $lead))->assertSessionHas('error');
    }

    public function test_lead_activity_log(): void
    {
        $lead = Lead::create([
            'source' => 'whatsapp', 'status' => 'new', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->post(route('erp.leads.activity.store', $lead), [
            'type' => 'call',
            'body' => 'Called client, discussed requirements',
        ])->assertRedirect();
        $this->assertDatabaseHas('lead_activities', ['lead_id' => $lead->id, 'type' => 'call']);
    }

    // ===== PROJECT TESTS =====

    public function test_project_index(): void
    {
        $this->actingAs($this->admin)->get(route('erp.projects.index'))->assertOk();
    }

    public function test_project_create_form(): void
    {
        $this->actingAs($this->admin)->get(route('erp.projects.create'))->assertOk();
    }

    public function test_project_store(): void
    {
        $this->actingAs($this->admin)->post(route('erp.projects.store'), [
            'client_id' => $this->client->id,
            'title' => 'Tax Return 2026',
            'department' => 'US TAX',
            'priority' => 'high',
            'start_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays(30)->format('Y-m-d'),
        ])->assertRedirect();

        $this->assertDatabaseHas('projects', ['title' => 'Tax Return 2026', 'status' => 'pending']);
    }

    public function test_project_show(): void
    {
        $project = Project::create([
            'project_number' => 'PRJ-TEST001',
            'client_id' => $this->client->id,
            'title' => 'Show Project',
            'department' => 'US TAX',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $this->admin->id,
            'assigned_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->get(route('erp.projects.show', $project))->assertOk();
    }

    public function test_project_transition(): void
    {
        $project = Project::create([
            'project_number' => 'PRJ-TEST002',
            'client_id' => $this->client->id,
            'title' => 'Transition Project',
            'department' => 'US TAX',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $this->admin->id,
            'assigned_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin)->post(route('erp.projects.transition', $project), ['status' => 'in_progress'])->assertRedirect();
        $this->assertDatabaseHas('projects', ['id' => $project->id, 'status' => 'in_progress']);
    }

    public function test_project_add_member(): void
    {
        $project = Project::create([
            'project_number' => 'PRJ-TEST003',
            'client_id' => $this->client->id,
            'title' => 'Member Project',
            'department' => 'US TAX',
            'status' => 'pending',
            'priority' => 'medium',
            'created_by' => $this->admin->id,
            'assigned_by' => $this->admin->id,
        ]);
        $user = $this->createUserWithRole('team_associate');
        $this->actingAs($this->admin)->post(route('erp.projects.members.store', $project), [
            'user_id' => $user->id,
            'role_in_project' => 'member',
        ])->assertRedirect();
        $this->assertDatabaseHas('project_members', ['project_id' => $project->id, 'user_id' => $user->id]);
    }

    // ===== DEPARTMENT TESTS =====

    public function test_department_index(): void
    {
        $this->actingAs($this->admin)->get(route('erp.departments.index'))->assertOk();
    }

    public function test_department_store(): void
    {
        $this->actingAs($this->admin)->post(route('erp.departments.store'), [
            'name' => 'Test Dept',
            'slug' => 'test-dept',
        ])->assertRedirect();
        $this->assertDatabaseHas('departments', ['slug' => 'test-dept']);
    }

    // ===== FINANCE APPROVAL TESTS =====

    public function test_finance_approval_index(): void
    {
        $this->actingAs($this->admin)->get(route('erp.finance-approvals.index'))->assertOk();
    }

    public function test_finance_approval_approve(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST001',
            'client_id' => $this->client->id,
            'created_by' => $this->admin->id,
            'status' => 'locked',
            'total' => 5000,
            'balance' => 5000,
            'subtotal' => 5000,
            'currency' => 'PKR',
        ]);

        $this->actingAs($this->admin)->post(route('erp.finance-approvals.approve', $invoice))->assertRedirect();
        $this->assertDatabaseHas('invoice_approvals', ['invoice_id' => $invoice->id, 'status' => 'approved']);
    }

    public function test_finance_approval_reject(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST002',
            'client_id' => $this->client->id,
            'created_by' => $this->admin->id,
            'status' => 'locked',
            'total' => 3000,
            'balance' => 3000,
            'subtotal' => 3000,
            'currency' => 'PKR',
        ]);

        $this->actingAs($this->admin)->post(route('erp.finance-approvals.reject', $invoice), [
            'notes' => 'Missing documentation',
        ])->assertRedirect();
        $this->assertDatabaseHas('invoice_approvals', ['invoice_id' => $invoice->id, 'status' => 'rejected']);
        $this->assertDatabaseHas('invoices', ['id' => $invoice->id, 'status' => 'draft']);
    }

    public function test_duplicate_approval_rejected(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST003',
            'client_id' => $this->client->id,
            'created_by' => $this->admin->id,
            'status' => 'locked',
            'total' => 2000,
            'balance' => 2000,
            'subtotal' => 2000,
            'currency' => 'PKR',
        ]);

        $this->actingAs($this->admin)->post(route('erp.finance-approvals.approve', $invoice))->assertRedirect();
        $this->actingAs($this->admin)->post(route('erp.finance-approvals.reject', $invoice), ['notes' => 'Test'])
            ->assertSessionHas('error');
    }

    // ===== ROLE TESTS =====

    public function test_team_associate_lead_read(): void
    {
        $associate = $this->createUserWithRole('team_associate');
        $this->actingAs($associate)->get(route('erp.leads.index'))->assertOk();
    }

    public function test_team_associate_project_read(): void
    {
        $associate = $this->createUserWithRole('team_associate');
        $this->actingAs($associate)->get(route('erp.projects.index'))->assertOk();
    }
}
