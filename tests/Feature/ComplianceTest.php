<?php

namespace Tests\Feature;

use App\Http\Middleware\SessionSecurity;
use App\Models\Client;
use App\Models\ComplianceRecord;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class ComplianceTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private $financeUser;

    private $csrUser;

    private $associateUser;

    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('private');
        $admin = $this->createUserWithRole('super_admin');
        $this->financeUser = $this->createUserWithRole('finance');
        $this->csrUser = $this->createUserWithRole('csr_manager');
        $this->associateUser = $this->createUserWithRole('team_associate');
        $this->client = Client::create(['business_name' => 'Test Client', 'status' => 'active', 'created_by' => $admin->id]);
    }

    private function asCsr(): InteractsWithRoles|self
    {
        $this->actingAs($this->csrUser);
        $this->withoutMiddleware(SessionSecurity::class);

        return $this;
    }

    private function asFinance(): InteractsWithRoles|self
    {
        $this->actingAs($this->financeUser);
        $this->withoutMiddleware(SessionSecurity::class);

        return $this;
    }

    public function test_finance_can_view_compliance(): void
    {
        $this->actingAs($this->financeUser);
        $this->get('/erp/compliance')->assertStatus(200);
    }

    public function test_csr_manager_can_view_compliance(): void
    {
        $this->actingAs($this->csrUser);
        $this->get('/erp/compliance')->assertStatus(200);
    }

    public function test_team_associate_cannot_view_compliance(): void
    {
        $this->actingAs($this->associateUser);
        $this->withoutMiddleware(SessionSecurity::class);
        $this->get('/erp/compliance')->assertStatus(302)->assertSessionHas('permission_denied');
    }

    public function test_create_compliance_record(): void
    {
        $this->asCsr();
        $response = $this->post('/erp/compliance', [
            'client_id' => $this->client->id,
            'document_type' => 'VAT Registration',
            'title' => 'VAT Certificate',
            'reference_number' => 'VAT-001',
            'issue_date' => '2026-01-01',
            'expiry_date' => '2027-01-01',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('compliance_records', [
            'client_id' => $this->client->id,
            'document_type' => 'VAT Registration',
        ]);
    }

    public function test_compliance_requires_client_id(): void
    {
        $this->asCsr();
        $response = $this->post('/erp/compliance', [
            'document_type' => 'VAT Registration',
        ]);

        $response->assertSessionHasErrors('client_id');
    }

    public function test_show_compliance_record(): void
    {
        $record = ComplianceRecord::create([
            'client_id' => $this->client->id,
            'document_type' => 'VAT Registration',
            'status' => 'active',
            'created_by' => $this->csrUser->id,
        ]);

        $this->actingAs($this->csrUser);
        $this->get("/erp/compliance/{$record->id}")->assertStatus(200);
    }

    public function test_update_compliance_record(): void
    {
        $record = ComplianceRecord::create([
            'client_id' => $this->client->id,
            'document_type' => 'VAT Registration',
            'status' => 'active',
            'created_by' => $this->csrUser->id,
        ]);

        $this->asCsr();
        $response = $this->patch("/erp/compliance/{$record->id}", [
            'client_id' => $record->client_id,
            'document_type' => 'Updated Type',
            'title' => 'Updated Title',
        ]);

        $response->assertRedirect();
        $this->assertEquals('Updated Type', $record->fresh()->document_type);
    }

    public function test_delete_compliance_record(): void
    {
        $record = ComplianceRecord::create([
            'client_id' => $this->client->id,
            'document_type' => 'VAT Registration',
            'status' => 'active',
            'created_by' => $this->csrUser->id,
        ]);

        $this->asCsr();
        $this->delete("/erp/compliance/{$record->id}")->assertRedirect();
        $this->assertSoftDeleted('compliance_records', ['id' => $record->id]);
    }

    public function test_renew_compliance_without_invoice(): void
    {
        $record = ComplianceRecord::create([
            'client_id' => $this->client->id,
            'document_type' => 'VAT Registration',
            'status' => 'active',
            'created_by' => $this->csrUser->id,
        ]);

        $this->asFinance();
        $this->post("/erp/compliance/{$record->id}/renew", [
            'amount' => 0,
        ])->assertRedirect();

        $this->assertEquals('renewed', $record->fresh()->status);
    }

    public function test_renew_compliance_with_invoice(): void
    {
        $record = ComplianceRecord::create([
            'client_id' => $this->client->id,
            'document_type' => 'VAT Registration',
            'status' => 'active',
            'created_by' => $this->csrUser->id,
        ]);

        $this->asFinance();
        $response = $this->post("/erp/compliance/{$record->id}/renew", [
            'amount' => 5000,
            'currency' => 'USD',
        ]);

        $this->assertEquals('renewed', $record->fresh()->status);
        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'total' => 5000,
        ]);
    }

    public function test_compliance_filter_by_status(): void
    {
        ComplianceRecord::create([
            'client_id' => $this->client->id,
            'document_type' => 'VAT',
            'status' => 'active',
            'created_by' => $this->csrUser->id,
        ]);

        $this->actingAs($this->csrUser);
        $this->get('/erp/compliance?status=active')->assertStatus(200);
    }

    public function test_compliance_create_with_file(): void
    {
        $this->asCsr();
        $file = UploadedFile::fake()->create('doc.pdf', 100, 'application/pdf');

        $response = $this->post('/erp/compliance', [
            'client_id' => $this->client->id,
            'document_type' => 'Tax Return',
            'title' => 'Tax Return 2026',
            'expiry_date' => now()->addYear()->toDateString(),
            'document_file' => $file,
        ]);

        $response->assertRedirect();
    }
}
