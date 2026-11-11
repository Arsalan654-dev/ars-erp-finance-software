<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\InteractsWithRoles;

class AuditTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);
        $this->admin = $this->createUserWithRole('super_admin');

        \App\Models\LedgerAccount::insert([
            ['code' => '1200', 'name' => 'Cash/Bank', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_client_create_is_logged(): void
    {
        $this->actingAs($this->admin);
        $this->post('/erp/clients', ['business_name' => 'Audit Test Corp', 'status' => 'active']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CLIENT_CREATE', 'entity_type' => 'client']);
    }

    public function test_client_update_is_logged(): void
    {
        $client = Client::create(['business_name' => 'Original', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->actingAs($this->admin);
        $this->patch("/erp/clients/{$client->id}", ['business_name' => 'Updated', 'status' => 'active']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'CLIENT_UPDATE', 'entity_type' => 'client', 'entity_id' => $client->id]);
    }

    public function test_invoice_lock_is_logged(): void
    {
        $client = Client::create(['business_name' => 'Audit Inv Client', 'status' => 'active', 'created_by' => $this->admin->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-AUDIT-001', 'client_id' => $client->id,
            'currency' => 'USD', 'subtotal' => 500, 'total' => 500, 'balance' => 500,
            'status' => 'draft', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->admin->id,
        ]);
        $this->actingAs($this->admin);
        $this->post("/erp/invoices/{$invoice->id}/lock");
        $this->assertDatabaseHas('audit_logs', ['action' => 'INVOICE_LOCK', 'entity_type' => 'invoice', 'entity_id' => $invoice->id]);
    }

    public function test_payment_verify_is_logged(): void
    {
        $client = Client::create(['business_name' => 'Audit Pay Client', 'status' => 'active', 'created_by' => $this->admin->id]);
        $client->email = 'audit@test.com';
        $client->save();
        $invoice = Invoice::create([
            'invoice_number' => 'INV-AUDIT-002', 'client_id' => $client->id,
            'currency' => 'USD', 'subtotal' => 250, 'total' => 250, 'balance' => 250,
            'status' => 'locked', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->admin->id,
        ]);
        $payment = Payment::create([
            'payment_number' => 'PAY-AUDIT-001', 'client_id' => $client->id,
            'currency' => 'USD', 'amount' => 250, 'status' => 'pending',
            'payment_date' => now()->toDateString(), 'recorded_by' => $this->admin->id,
        ]);
        $payment->invoices()->attach($invoice->id, ['amount' => 250]);
        $this->actingAs($this->admin);
        $this->post("/erp/finance/payments/{$payment->id}/acknowledge");
        $this->post("/erp/finance/payments/{$payment->id}/verify");
        $this->assertDatabaseHas('audit_logs', ['action' => 'PAYMENT_ACKNOWLEDGE', 'entity_type' => 'payment', 'entity_id' => $payment->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'PAYMENT_VERIFY', 'entity_type' => 'payment', 'entity_id' => $payment->id]);
    }

    public function test_audit_log_records_user_id(): void
    {
        $this->actingAs($this->admin);
        AuditService::log('TEST_ACTION', 'test', 1, ['key' => 'value']);
        $this->assertDatabaseHas('audit_logs', ['action' => 'TEST_ACTION', 'user_id' => $this->admin->id]);
    }
}
