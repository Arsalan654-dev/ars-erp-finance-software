<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\Quotation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\InteractsWithRoles;

class QuotationTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private $financeUser;
    private $csrUser;
    private $associateUser;
    private $superAdmin;
    private $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->superAdmin = $this->createUserWithRole('super_admin');
        $this->financeUser = $this->createUserWithRole('finance');
        $this->csrUser = $this->createUserWithRole('csr_manager');
        $this->associateUser = $this->createUserWithRole('team_associate');
        $this->client = Client::create(['business_name' => 'Quote Client', 'status' => 'active', 'created_by' => $this->superAdmin->id]);
        $this->client->email = 'quote@test.com';
        $this->client->save();

        LedgerAccount::insert([
            ['code' => '1200', 'name' => 'Cash/Bank', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function asFinance()
    {
        $this->actingAs($this->financeUser);
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);
        return $this;
    }

    public function test_finance_can_view_quotations(): void
    {
        $this->actingAs($this->financeUser);
        $this->get('/erp/quotations')->assertStatus(200);
    }

    public function test_create_quotation(): void
    {
        $this->asFinance();
        $response = $this->post('/erp/quotations', [
            'client_id' => $this->client->id,
            'currency' => 'USD',
            'valid_until' => now()->addDays(30)->toDateString(),
            'items' => [
                ['description' => 'Tax Filing', 'quantity' => 1, 'unit_price' => 1000],
                ['description' => 'Consulting', 'quantity' => 2, 'unit_price' => 500],
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quotations', [
            'client_id' => $this->client->id,
            'status' => 'draft',
            'subtotal' => 2000,
            'total' => 2000,
        ]);
    }

    public function test_quotation_requires_items(): void
    {
        $this->asFinance();
        $response = $this->post('/erp/quotations', [
            'client_id' => $this->client->id,
            'currency' => 'USD',
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_show_quotation(): void
    {
        $quotation = $this->createQuotation('draft');

        $this->actingAs($this->financeUser);
        $this->get("/erp/quotations/{$quotation->id}")->assertStatus(200);
    }

    public function test_edit_draft_quotation(): void
    {
        $quotation = $this->createQuotation('draft');

        $this->actingAs($this->financeUser);
        $this->get("/erp/quotations/{$quotation->id}/edit")->assertStatus(200);
    }

    public function test_cannot_edit_sent_quotation(): void
    {
        $quotation = $this->createQuotation('sent');

        $this->actingAs($this->financeUser);
        $this->get("/erp/quotations/{$quotation->id}/edit")->assertSessionHas('error');
    }

    public function test_update_draft_quotation(): void
    {
        $quotation = $this->createQuotation('draft');

        $this->asFinance();
        $response = $this->patch("/erp/quotations/{$quotation->id}", [
            'client_id' => $this->client->id,
            'currency' => 'GBP',
            'items' => [
                ['description' => 'Updated Service', 'quantity' => 1, 'unit_price' => 2000],
            ],
        ]);

        $response->assertRedirect();
        $this->assertEquals('GBP', $quotation->fresh()->currency);
    }

    public function test_send_quotation(): void
    {
        $quotation = $this->createQuotation('draft');

        $this->asFinance();
        $this->post("/erp/quotations/{$quotation->id}/send")->assertRedirect();
        $this->assertEquals('sent', $quotation->fresh()->status);
    }

    public function test_accept_quotation(): void
    {
        $quotation = $this->createQuotation('sent');

        $this->asFinance();
        $this->post("/erp/quotations/{$quotation->id}/accept")->assertRedirect();
        $this->assertEquals('accepted', $quotation->fresh()->status);
    }

    public function test_convert_accepted_quotation_to_invoice(): void
    {
        $quotation = $this->createQuotation('accepted');

        $this->asFinance();
        $response = $this->post("/erp/quotations/{$quotation->id}/convert");
        $response->assertRedirect();

        $this->assertEquals('converted', $quotation->fresh()->status);
        $this->assertDatabaseHas('invoices', [
            'client_id' => $this->client->id,
            'quotation_id' => $quotation->id,
            'total' => $quotation->total,
            'status' => 'draft',
        ]);
    }

    public function test_cannot_convert_draft_quotation(): void
    {
        $quotation = $this->createQuotation('draft');

        $this->asFinance();
        $this->post("/erp/quotations/{$quotation->id}/convert")->assertSessionHas('error');
        $this->assertDatabaseMissing('invoices', ['quotation_id' => $quotation->id]);
    }

    public function test_cannot_convert_sent_quotation(): void
    {
        $quotation = $this->createQuotation('sent');

        $this->asFinance();
        $this->post("/erp/quotations/{$quotation->id}/convert")->assertSessionHas('error');
    }

    public function test_delete_draft_quotation(): void
    {
        $quotation = $this->createQuotation('draft');

        $this->actingAs($this->superAdmin);
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);
        $this->delete("/erp/quotations/{$quotation->id}")->assertRedirect();
        $this->assertSoftDeleted('quotations', ['id' => $quotation->id]);
    }

    public function test_cannot_delete_sent_quotation(): void
    {
        $quotation = $this->createQuotation('sent');

        $this->actingAs($this->superAdmin);
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);
        $this->delete("/erp/quotations/{$quotation->id}")->assertSessionHas('error');
        $this->assertDatabaseHas('quotations', ['id' => $quotation->id]);
    }

    public function test_quotation_status_history_created_on_send(): void
    {
        $quotation = $this->createQuotation('draft');

        $this->asFinance();
        $this->post("/erp/quotations/{$quotation->id}/send");

        $this->assertDatabaseHas('quotation_status_history', [
            'quotation_id' => $quotation->id,
            'from_status' => 'draft',
            'to_status' => 'sent',
        ]);
    }

    public function test_quotation_number_auto_generated(): void
    {
        $this->asFinance();
        $this->post('/erp/quotations', [
            'client_id' => $this->client->id,
            'currency' => 'USD',
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 100],
            ],
        ]);

        $this->assertDatabaseHas('quotations', [
            'client_id' => $this->client->id,
        ]);

        $q = Quotation::where('client_id', $this->client->id)->first();
        $this->assertStringStartsWith('QTN-', $q->quotation_number);
    }

    public function test_quotation_with_discount_and_tax(): void
    {
        $this->asFinance();
        $this->post('/erp/quotations', [
            'client_id' => $this->client->id,
            'currency' => 'USD',
            'discount' => 100,
            'tax' => 50,
            'items' => [
                ['description' => 'Service', 'quantity' => 1, 'unit_price' => 1000],
            ],
        ]);

        $q = Quotation::where('client_id', $this->client->id)->first();
        $this->assertEquals(1000, $q->subtotal);
        $this->assertEquals(100, $q->discount);
        $this->assertEquals(50, $q->tax);
        $this->assertEquals(950, $q->total);
    }

    private function createQuotation(string $status): Quotation
    {
        $quotation = Quotation::create([
            'quotation_number' => 'QTN-TEST-' . uniqid(),
            'client_id' => $this->client->id,
            'currency' => 'USD',
            'subtotal' => 1000,
            'total' => 1000,
            'status' => $status,
            'created_by' => $this->financeUser->id,
        ]);

        $quotation->items()->create([
            'description' => 'Tax Filing',
            'quantity' => 1,
            'unit_price' => 1000,
            'total' => 1000,
        ]);

        return $quotation;
    }
}
