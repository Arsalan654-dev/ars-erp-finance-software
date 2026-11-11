<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\SalesPackage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class DocxRequirementsTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);
        $this->admin = $this->createUserWithRole('super_admin');
    }

    private function makeClient(string $name): Client
    {
        return Client::create(['business_name' => $name, 'status' => 'active', 'created_by' => $this->admin->id]);
    }

    private function makeInvoice(Client $client, string $description, array $overrides = []): Invoice
    {
        $invoice = Invoice::create(array_merge([
            'invoice_number' => 'INV-DOCX-'.uniqid(),
            'client_id' => $client->id,
            'currency' => 'USD',
            'subtotal' => 100, 'total' => 100,
            'status' => 'sent', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(),
            'created_by' => $this->admin->id,
        ], $overrides));

        $invoice->items()->create([
            'description' => $description,
            'quantity' => 1, 'unit_price' => 100, 'total' => 100,
        ]);

        return $invoice;
    }

    // ---- Payment screenshot mismatch (fraud flag) ----

    public function test_payment_requires_screenshot_amount(): void
    {
        $client = $this->makeClient('Mismatch Co');

        $this->actingAs($this->admin)->post(route('erp.finance.payments.store'), [
            'client_id' => $client->id,
            'currency' => 'USD',
            'amount' => 500,
            'screenshot' => UploadedFile::fake()->create('pay.png', 20, 'image/png'),
        ])->assertSessionHasErrors('screenshot_amount');
    }

    public function test_amount_mismatch_flags_payment_and_notifies_admin(): void
    {
        $finance = $this->createUserWithRole('finance');
        $client = $this->makeClient('Fraud Co');

        $this->actingAs($finance)->post(route('erp.finance.payments.store'), [
            'client_id' => $client->id,
            'currency' => 'USD',
            'amount' => 500,
            'screenshot_amount' => 100,
            'screenshot' => UploadedFile::fake()->create('pay.png', 20, 'image/png'),
        ])->assertRedirect();

        $payment = Payment::latest('id')->first();
        $this->assertNotNull($payment);
        $this->assertTrue((bool) $payment->amount_mismatch);
        $this->assertEquals(100, (float) $payment->screenshot_amount);
        $this->assertEquals($finance->id, $payment->recorded_by);

        // Direct admin notification (admin did not record it, so they are notified)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => 'payment_mismatch',
        ]);

        // Audit trail
        $this->assertDatabaseHas('audit_logs', ['action' => 'PAYMENT_AMOUNT_MISMATCH']);
    }

    public function test_matching_amount_is_not_flagged(): void
    {
        $client = $this->makeClient('Honest Co');

        $this->actingAs($this->admin)->post(route('erp.finance.payments.store'), [
            'client_id' => $client->id,
            'currency' => 'USD',
            'amount' => 500,
            'screenshot_amount' => 500,
            'screenshot' => UploadedFile::fake()->create('pay.png', 20, 'image/png'),
        ])->assertRedirect();

        $this->assertFalse((bool) Payment::latest('id')->first()->amount_mismatch);
    }

    // ---- Lost-reason template ----

    public function test_lead_lost_requires_template_reason(): void
    {
        $lead = Lead::create([
            'source' => 'manual', 'status' => 'contacted', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)->post(route('erp.leads.transition', $lead), [
            'status' => 'lost',
            'lost_reason' => 'Not on the template list',
        ])->assertSessionHasErrors('lost_reason');

        $this->assertEquals('contacted', $lead->fresh()->status);
    }

    public function test_lead_lost_stores_template_reason_and_note(): void
    {
        $csr = $this->createUserWithRole('csr_manager');
        $lead = Lead::create([
            'source' => 'manual', 'status' => 'contacted', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $csr->id,
        ]);

        $reason = config('erp.lead_lost_reasons')[0];

        $this->actingAs($csr)->post(route('erp.leads.transition', $lead), [
            'status' => 'lost',
            'lost_reason' => $reason,
            'lost_note' => 'Budget frozen until next quarter.',
        ])->assertSessionHas('success');

        $lead->refresh();
        $this->assertEquals('lost', $lead->status);
        $this->assertEquals($reason, $lead->lost_reason);
        $this->assertEquals('Budget frozen until next quarter.', $lead->lost_note);

        // Admin was notified (the CSR who closed it is not)
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->admin->id,
            'type' => 'lead_lost',
        ]);
    }

    // ---- Real follow_up status ----

    public function test_contacted_can_move_to_follow_up(): void
    {
        $lead = Lead::create([
            'source' => 'manual', 'status' => 'contacted', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $this->admin->id,
        ]);

        $this->assertTrue($lead->canTransitionTo('follow_up'));

        $this->actingAs($this->admin)->post(route('erp.leads.transition', $lead), [
            'status' => 'follow_up',
        ])->assertSessionHas('success');

        $this->assertEquals('follow_up', $lead->fresh()->status);
    }

    // ---- Quotation: exchange rate + duplicate ----

    public function test_quotation_duplicate_creates_new_number_and_flags_copy(): void
    {
        $client = $this->makeClient('Dup Co');

        $original = Quotation::create([
            'quotation_number' => 'QTN-ORIG-001',
            'client_id' => $client->id,
            'currency' => 'GBP',
            'exchange_rate' => 350.5,
            'subtotal' => 200, 'total' => 200,
            'status' => 'sent',
            'created_by' => $this->admin->id,
        ]);
        $original->items()->create(['description' => 'Consulting', 'quantity' => 2, 'unit_price' => 100, 'total' => 200]);

        $this->actingAs($this->admin)
            ->post(route('erp.quotations.duplicate', $original))
            ->assertRedirect();

        $copy = Quotation::latest('id')->first();
        $this->assertNotEquals($original->id, $copy->id);
        $this->assertNotEquals($original->quotation_number, $copy->quotation_number);
        $this->assertEquals($original->id, $copy->duplicate_of_id);
        $this->assertEquals(1, $copy->items()->count());
        $this->assertEquals(350.5, (float) $copy->exchange_rate);
        $this->assertTrue($copy->isDuplicate());
    }

    public function test_quotation_create_form_shows_exchange_rate_and_packages(): void
    {
        $this->makeClient('Form Co');
        SalesPackage::create(['name' => 'SEO Starter', 'unit_price' => 499, 'currency' => 'GBP', 'is_active' => true]);

        $this->actingAs($this->admin)
            ->get(route('erp.quotations.create'))
            ->assertOk()
            ->assertSee('Exchange Rate')
            ->assertSee('Add from Sales Package')
            ->assertSee('SEO Starter');
    }

    // ---- Invoice: shared requirements badge ----

    public function test_invoice_index_flags_shared_requirements_across_clients(): void
    {
        $clientA = $this->makeClient('Client A');
        $clientB = $this->makeClient('Client B');

        $this->makeInvoice($clientA, 'Annual bookkeeping package for small business');
        $this->makeInvoice($clientB, 'annual bookkeeping package for small business');

        $response = $this->actingAs($this->admin)->get(route('erp.invoices.index'));
        $response->assertOk()->assertSee('Same requirements');
    }

    public function test_invoice_index_does_not_flag_unique_requirements(): void
    {
        $clientA = $this->makeClient('Solo Client');
        $this->makeInvoice($clientA, 'Unique one-off service for client A only');

        $this->actingAs($this->admin)
            ->get(route('erp.invoices.index'))
            ->assertOk()
            ->assertDontSee('Same requirements');
    }

    // ---- Super admin deletes ----

    public function test_super_admin_can_delete_draft_invoice(): void
    {
        $client = $this->makeClient('Draft Co');
        $invoice = $this->makeInvoice($client, 'Draft line', ['status' => 'draft']);

        $this->actingAs($this->admin)
            ->delete(route('erp.invoices.destroy', $invoice))
            ->assertRedirect(route('erp.invoices.index'));

        $this->assertNotNull(\App\Models\Invoice::withTrashed()->find($invoice->id));
        $this->assertNotNull(\App\Models\Invoice::withTrashed()->find($invoice->id)->deleted_at);
    }

    public function test_locked_invoice_cannot_be_deleted(): void
    {
        $client = $this->makeClient('Locked Co');
        $invoice = $this->makeInvoice($client, 'Locked line', ['status' => 'locked']);

        $this->actingAs($this->admin)
            ->delete(route('erp.invoices.destroy', $invoice))
            ->assertSessionHas('error');

        $this->assertFalse($invoice->fresh()->trashed());
        $this->assertEquals('locked', $invoice->fresh()->status);
    }

    // ---- Sales packages ----

    public function test_sales_package_crud_accessible_for_super_admin(): void
    {
        $this->actingAs($this->admin)->get(route('erp.sales-packages.index'))->assertOk()->assertSee('Sales Packages');

        $this->actingAs($this->admin)->post(route('erp.sales-packages.store'), [
            'name' => 'VAT Return Pack',
            'unit_price' => 250,
            'currency' => 'GBP',
        ])->assertRedirect(route('erp.sales-packages.index'));

        $this->assertDatabaseHas('sales_packages', ['name' => 'VAT Return Pack']);
    }

    // ---- Admin reports ----

    public function test_admin_reports_page_renders(): void
    {
        $client = $this->makeClient('Report Co');
        $this->makeInvoice($client, 'Reported line');

        $this->actingAs($this->admin)
            ->get(route('erp.admin.reports'))
            ->assertOk()
            ->assertSee('Admin Reports')
            ->assertSee('Screenshot Mismatch');
    }
}
