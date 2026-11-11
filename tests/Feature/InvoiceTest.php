<?php

namespace Tests\Feature;

use App\Http\Middleware\SessionSecurity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $financeUser;

    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->financeUser = $this->createUserWithRole('finance');
        $admin = $this->createUserWithRole('super_admin');
        $this->client = Client::create([
            'business_name' => 'Invoice Test Client', 'status' => 'active', 'created_by' => $admin->id,
        ]);

        LedgerAccount::insert([
            ['code' => '1200', 'name' => 'Cash/Bank', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function createInvoice(string $status = 'draft'): Invoice
    {
        return Invoice::create([
            'invoice_number' => 'INV-TEST-'.uniqid(), 'client_id' => $this->client->id,
            'currency' => 'USD', 'subtotal' => 1000, 'total' => 1000, 'balance' => 1000,
            'status' => $status, 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->financeUser->id,
        ]);
    }

    public function test_draft_can_be_locked(): void
    {
        $invoice = $this->createInvoice('draft');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/lock")->assertRedirect();
        $this->assertEquals('locked', $invoice->fresh()->status);
    }

    public function test_locked_can_be_sent(): void
    {
        $invoice = $this->createInvoice('locked');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/send")->assertRedirect();
        $this->assertEquals('sent', $invoice->fresh()->status);
    }

    public function test_draft_cannot_be_sent_directly(): void
    {
        $invoice = $this->createInvoice('draft');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/send")->assertSessionHas('error');
        $this->assertEquals('draft', $invoice->fresh()->status);
    }

    public function test_draft_can_be_cancelled(): void
    {
        $invoice = $this->createInvoice('draft');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/cancel")->assertRedirect();
        $this->assertEquals('cancelled', $invoice->fresh()->status);
    }

    public function test_locked_can_be_cancelled(): void
    {
        $invoice = $this->createInvoice('locked');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/cancel")->assertRedirect();
        $this->assertEquals('cancelled', $invoice->fresh()->status);
    }

    public function test_cancelled_cannot_be_sent(): void
    {
        $invoice = $this->createInvoice('cancelled');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/send")->assertSessionHas('error');
    }

    public function test_cancelled_cannot_be_locked(): void
    {
        $invoice = $this->createInvoice('cancelled');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/lock")->assertSessionHas('error');
    }

    public function test_paid_cannot_be_cancelled(): void
    {
        $invoice = $this->createInvoice('paid');
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/cancel")->assertSessionHas('error');
    }

    public function test_team_associate_can_access_assigned_client_invoice_pdf(): void
    {
        $associate = $this->createUserWithRole('team_associate');
        $assignedClient = Client::create(['business_name' => 'Assigned', 'status' => 'active', 'created_by' => $this->financeUser->id]);

        Task::create([
            'title' => 'Tax Return', 'task_number' => 'TSK-001', 'client_id' => $assignedClient->id,
            'status' => 'assigned', 'created_by' => $this->financeUser->id,
        ])->assignments()->create(['user_id' => $associate->id, 'assigned_by' => $this->financeUser->id]);

        $invAssigned = $this->createInvoice('locked');
        $invAssigned->update(['client_id' => $assignedClient->id]);

        $this->actingAs($associate);
        $this->get("/erp/invoices/{$invAssigned->id}/pdf")->assertStatus(200);
    }

    public function test_team_associate_cannot_access_unrelated_client_invoice_pdf(): void
    {
        $associate = $this->createUserWithRole('team_associate');
        $unrelatedClient = Client::create(['business_name' => 'Unrelated', 'status' => 'active', 'created_by' => $this->financeUser->id]);

        $invUnrelated = $this->createInvoice('locked');
        $invUnrelated->update(['client_id' => $unrelatedClient->id]);

        $this->actingAs($associate);
        $this->withoutMiddleware(SessionSecurity::class);
        $this->get("/erp/invoices/{$invUnrelated->id}/pdf")->assertStatus(302)->assertSessionHas('permission_denied');
    }
}
