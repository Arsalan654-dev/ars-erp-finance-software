<?php

namespace Tests\Feature;

use App\Http\Middleware\SessionSecurity;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quotation;
use App\Models\SalesPackage;
use App\Models\User;
use App\Services\CsrAssigner;
use Database\Seeders\SalesPackageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class RequirementPointsTest extends TestCase
{
    use InteractsWithRoles, RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->withoutMiddleware(SessionSecurity::class);
        $this->admin = $this->createUserWithRole('super_admin');
    }

    // ===== POINT 1: lead sources include facebook / linkedin =====

    public function test_lead_form_offers_facebook_and_linkedin_sources(): void
    {
        $this->actingAs($this->admin)
            ->get(route('erp.leads.create'))
            ->assertOk()
            ->assertSee('Facebook')
            ->assertSee('Linkedin');
    }

    public function test_lead_accepts_facebook_and_linkedin_sources(): void
    {
        $this->actingAs($this->admin);

        foreach (['facebook', 'linkedin'] as $source) {
            $this->post(route('erp.leads.store'), [
                'business_name' => 'Social Lead '.$source,
                'source' => $source,
                'campaign_name' => ucfirst($source).' Ads - Oct 2026',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])->assertRedirect();

            $this->assertDatabaseHas('leads', [
                'business_name' => 'Social Lead '.$source,
                'source' => $source,
            ]);
        }
    }

    public function test_lead_rejects_unknown_source(): void
    {
        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Bad Source',
                'source' => 'tiktok',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])
            ->assertSessionHasErrors('source');
    }

    // ===== POINT 2: leads search by client number =====

    public function test_lead_search_matches_client_number(): void
    {
        $client = Client::create([
            'business_name' => 'Number Search Co',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Number Search Co',
                'email' => 'numbersearch@example.com',
                'source' => 'website',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('erp.leads.index', ['q' => $client->client_number]))
            ->assertOk()
            ->assertSee('Number Search Co');
    }

    // ===== POINT 3: pipeline cards drill down =====

    public function test_pipeline_stat_cards_are_links_to_filtered_lists(): void
    {
        $this->actingAs($this->admin)
            ->get(route('erp.leads.pipeline'))
            ->assertOk()
            ->assertSee(route('erp.leads.index', ['status' => 'follow_up']), false)
            ->assertSee(route('erp.leads.index', ['status' => 'quotation']), false)
            ->assertSee(route('erp.leads.index', ['status' => 'lost']), false)
            ->assertSee(route('erp.clients.index', ['payment' => 'partial']), false)
            ->assertSee(route('erp.clients.index', ['payment' => 'paid']), false)
            ->assertSee(route('erp.clients.index', ['with' => 'invoice']), false);
    }

    public function test_clients_index_filters_by_payment_state(): void
    {
        $paidClient = Client::create(['business_name' => 'Paid Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        $dueClient = Client::create(['business_name' => 'Due Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        $otherClient = Client::create(['business_name' => 'No Invoice Co', 'status' => 'active', 'created_by' => $this->admin->id]);

        Invoice::create([
            'invoice_number' => 'INV-00001',
            'client_id' => $paidClient->id,
            'currency' => 'USD',
            'subtotal' => 100, 'discount' => 0, 'tax' => 0,
            'total' => 100, 'paid_amount' => 100, 'balance' => 0,
            'status' => 'sent', 'payment_status' => 'paid',
            'created_by' => $this->admin->id,
        ]);
        Invoice::create([
            'invoice_number' => 'INV-00002',
            'client_id' => $dueClient->id,
            'currency' => 'USD',
            'subtotal' => 200, 'discount' => 0, 'tax' => 0,
            'total' => 200, 'paid_amount' => 50, 'balance' => 150,
            'status' => 'sent', 'payment_status' => 'partial',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('erp.clients.index', ['payment' => 'paid']))
            ->assertOk()
            ->assertSee('Paid Co')
            ->assertDontSee('Due Co');

        $this->actingAs($this->admin)
            ->get(route('erp.clients.index', ['payment' => 'partial']))
            ->assertOk()
            ->assertSee('Due Co')
            ->assertDontSee('Paid Co');
    }

    // ===== POINT 9: duplicate lead flag + badge =====

    public function test_repeat_lead_is_flagged_duplicate(): void
    {
        Client::create([
            'business_name' => 'Dup Co',
            'email' => 'dup@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Dup Co',
                'email' => 'dup@example.com',
                'source' => 'referral',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $lead = Lead::where('email', 'dup@example.com')->firstOrFail();
        $this->assertTrue((bool) $lead->is_duplicate);
    }

    public function test_fresh_lead_is_not_flagged_duplicate(): void
    {
        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Fresh Co',
                'email' => 'fresh@example.com',
                'source' => 'website',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $lead = Lead::where('email', 'fresh@example.com')->firstOrFail();
        $this->assertFalse((bool) $lead->is_duplicate);
    }

    public function test_leads_index_shows_duplicate_badge(): void
    {
        Client::create([
            'business_name' => 'Badge Co',
            'email' => 'badge@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Badge Co',
                'email' => 'badge@example.com',
                'source' => 'referral',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('erp.leads.index'))
            ->assertOk()
            ->assertSee('Duplicate');
    }

    // ===== POINT 11: new client distributed to a CSR =====

    public function test_new_client_is_assigned_to_a_csr(): void
    {
        $csr = $this->createUserWithRole('csr_manager');

        $this->actingAs($this->admin)
            ->post(route('erp.clients.store'), [
                'business_name' => 'Assigned Client Co',
                'status' => 'active',
            ])
            ->assertRedirect();

        $client = Client::where('business_name', 'Assigned Client Co')->firstOrFail();
        $this->assertSame($csr->id, $client->assigned_to);
    }

    public function test_client_owner_wins_for_repeat_lead_assignment(): void
    {
        $owner = $this->createUserWithRole('csr_manager');
        $other = $this->createUserWithRole('csr_manager');

        $client = Client::create([
            'business_name' => 'Owned Co',
            'email' => 'owned@example.com',
            'status' => 'lead',
            'assigned_to' => $owner->id,
            'created_by' => $this->admin->id,
        ]);
        // Give the other CSR the latest lead so only assigned_to points to owner.
        Lead::create([
            'client_id' => $client->id,
            'source' => 'manual',
            'business_name' => 'Owned Co',
            'status' => 'contacted',
            'priority' => 'medium',
            'department' => 'GENERAL',
            'assigned_to' => $other->id,
            'created_by' => $this->admin->id,
        ]);

        $this->assertSame($owner->id, CsrAssigner::previousCsr($client)?->id);
    }

    // ===== POINT 14: invoice duplicate with a new number =====

    public function test_duplicate_invoice_gets_a_new_number(): void
    {
        $client = Client::create(['business_name' => 'Dup Inv Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-00099',
            'client_id' => $client->id,
            'currency' => 'USD',
            'subtotal' => 100, 'discount' => 0, 'tax' => 0,
            'total' => 100, 'paid_amount' => 0, 'balance' => 100,
            'status' => 'draft', 'payment_status' => 'unpaid',
            'created_by' => $this->admin->id,
        ]);
        $invoice->items()->create([
            'description' => 'Consulting', 'quantity' => 1, 'unit_price' => 100, 'total' => 100,
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.invoices.duplicate', $invoice))
            ->assertRedirect();

        $copy = Invoice::where('duplicate_of_id', $invoice->id)->firstOrFail();
        $this->assertNotSame($invoice->invoice_number, $copy->invoice_number);
        $this->assertSame('draft', $copy->status);
        $this->assertSame(100.0, (float) $copy->total);
        $this->assertSame(1, $copy->items()->count());
    }

    public function test_invoice_shows_duplicate_badge(): void
    {
        $client = Client::create(['business_name' => 'Badge Inv Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        $invoice = Invoice::create([
            'invoice_number' => 'INV-00100',
            'client_id' => $client->id,
            'currency' => 'USD',
            'subtotal' => 50, 'discount' => 0, 'tax' => 0,
            'total' => 50, 'paid_amount' => 0, 'balance' => 50,
            'status' => 'draft', 'payment_status' => 'unpaid',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.invoices.duplicate', $invoice))
            ->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('erp.invoices.index'))
            ->assertOk()
            ->assertSee('Duplicate');
    }

    // ===== POINT 15: editing a quotation clears the Duplicate badge =====

    public function test_updating_quotation_clears_duplicate_flag(): void
    {
        $client = Client::create(['business_name' => 'Fix Quo Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        $original = Quotation::create([
            'quotation_number' => 'QTN-00050',
            'client_id' => $client->id,
            'currency' => 'USD',
            'subtotal' => 100, 'discount' => 0, 'tax' => 0, 'total' => 100,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);
        $copy = Quotation::create([
            'quotation_number' => 'QTN-00051',
            'client_id' => $client->id,
            'duplicate_of_id' => $original->id,
            'currency' => 'USD',
            'subtotal' => 100, 'discount' => 0, 'tax' => 0, 'total' => 100,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);
        $copy->items()->create(['description' => 'Service', 'quantity' => 1, 'unit_price' => 100, 'total' => 100]);

        $this->actingAs($this->admin)
            ->patch(route('erp.quotations.update', $copy), [
                'client_id' => $client->id,
                'currency' => 'USD',
                'items' => [
                    ['description' => 'Corrected Service', 'quantity' => 1, 'unit_price' => 100],
                ],
            ])
            ->assertRedirect();

        $this->assertNull($copy->fresh()->duplicate_of_id);
    }

    // ===== POINT 18: Created By (CSR) attribution =====

    public function test_quotation_index_shows_creator_column(): void
    {
        $client = Client::create(['business_name' => 'Creator Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        Quotation::create([
            'quotation_number' => 'QTN-00060',
            'client_id' => $client->id,
            'currency' => 'USD',
            'subtotal' => 10, 'discount' => 0, 'tax' => 0, 'total' => 10,
            'status' => 'draft',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('erp.quotations.index'))
            ->assertOk()
            ->assertSee('Created By (CSR)')
            ->assertSee($this->admin->name);
    }

    public function test_invoice_index_shows_creator_column(): void
    {
        $client = Client::create(['business_name' => 'Inv Creator Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        Invoice::create([
            'invoice_number' => 'INV-00110',
            'client_id' => $client->id,
            'currency' => 'USD',
            'subtotal' => 10, 'discount' => 0, 'tax' => 0,
            'total' => 10, 'paid_amount' => 0, 'balance' => 10,
            'status' => 'draft', 'payment_status' => 'unpaid',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('erp.invoices.index'))
            ->assertOk()
            ->assertSee('Created By (CSR)')
            ->assertSee($this->admin->name);
    }

    // ===== POINT 19: per-person work report =====

    public function test_super_admin_can_view_work_report(): void
    {
        $this->actingAs($this->admin)
            ->get(route('erp.admin.work-report'))
            ->assertOk()
            ->assertSee('Work Report')
            ->assertSee($this->admin->name)
            ->assertSee('Remaining');
    }

    public function test_work_report_renders_with_existing_audit_history(): void
    {
        // AuditLog disables timestamps, so created_at comes back as a raw
        // string — the report must still render (regression: 500 on live).
        DB::table('audit_logs')->insert([
            'user_id' => $this->admin->id,
            'action' => 'LOGIN',
            'entity_type' => null,
            'entity_id' => null,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test',
            'metadata' => null,
            'created_at' => now()->subHour()->toDateTimeString(),
        ]);

        $this->actingAs($this->admin)
            ->get(route('erp.admin.work-report'))
            ->assertOk()
            ->assertSee('Work Report')
            ->assertSee('ago');
    }

    public function test_work_report_filters_by_date_range(): void
    {
        $this->actingAs($this->admin)
            ->get(route('erp.admin.work-report', ['from' => '2026-01-01', 'to' => '2026-12-31']))
            ->assertOk()
            ->assertSee('2026-01-01')
            ->assertSee('2026-12-31');
    }

    public function test_work_report_hidden_from_non_admins(): void
    {
        $hr = $this->createUserWithRole('hr');

        $this->actingAs($hr)
            ->get(route('erp.admin.work-report'))
            ->assertStatus(302)
            ->assertSessionHas('permission_denied');
    }

    // ===== POINT 16: sales package bundles drop into quotations =====

    public function test_package_seeder_creates_usa_and_uk_bundles(): void
    {
        $this->seed(SalesPackageSeeder::class);

        $this->assertSame(13, SalesPackage::count());

        $texas = SalesPackage::where('name', 'USA Texas LLC Package')->firstOrFail();
        $this->assertSame(620.0, (float) $texas->unit_price);
        $this->assertSame(8, $texas->items()->count());

        $texasAmazon = SalesPackage::where('name', 'USA Texas LLC with Amazon Package')->firstOrFail();
        $this->assertSame(750.0, (float) $texasAmazon->unit_price);
        $this->assertSame(10, $texasAmazon->items()->count());

        $coloradoAmazon = SalesPackage::where('name', 'USA Colorado LLC with Amazon Package')->firstOrFail();
        $this->assertSame(550.0, (float) $coloradoAmazon->unit_price);

        $uk = SalesPackage::where('name', 'UK Ltd Formation')->firstOrFail();
        $this->assertSame('GBP', $uk->currency);
        $this->assertSame(305.0, (float) $uk->unit_price);

        $trademark = SalesPackage::where('name', 'UK Trademark Registration')->firstOrFail();
        $this->assertSame(450.0, (float) $trademark->unit_price);
    }

    public function test_package_seeder_is_idempotent(): void
    {
        $this->seed(SalesPackageSeeder::class);
        $this->seed(SalesPackageSeeder::class);

        $this->assertSame(13, SalesPackage::count());
        $texas = SalesPackage::where('name', 'USA Texas LLC Package')->firstOrFail();
        $this->assertSame(8, $texas->items()->count());
    }

    public function test_quotation_form_lists_bundle_lines_in_picker(): void
    {
        $this->seed(SalesPackageSeeder::class);

        $this->actingAs($this->admin)
            ->get(route('erp.quotations.create'))
            ->assertOk()
            ->assertSee('USA Texas LLC Package')
            ->assertSee('(10 lines)', false)
            ->assertSee('Express EIN', false);
    }

    public function test_package_bundle_lines_can_be_stored_as_quotation_items(): void
    {
        $this->seed(SalesPackageSeeder::class);

        $client = Client::create(['business_name' => 'Bundle Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        $package = SalesPackage::with('items')->where('name', 'USA Florida LLC Package')->firstOrFail();

        $this->actingAs($this->admin)
            ->post(route('erp.quotations.store'), [
                'client_id' => $client->id,
                'currency' => 'USD',
                'items' => collect($package->quotationLines())->map(fn ($line) => [
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                ])->all(),
            ])
            ->assertRedirect();

        $quotation = Quotation::where('client_id', $client->id)->firstOrFail();
        $this->assertSame(8, $quotation->items()->count());
        $this->assertSame(445.0, (float) $quotation->total);
    }

    public function test_legacy_single_price_package_still_works(): void
    {
        $package = SalesPackage::create([
            'name' => 'Legacy Single',
            'currency' => 'USD',
            'unit_price' => 99,
            'is_active' => true,
        ]);

        $lines = $package->fresh()->quotationLines();
        $this->assertCount(1, $lines);
        $this->assertSame('Legacy Single', $lines[0]['description']);
        $this->assertSame(99.0, $lines[0]['unit_price']);

        $this->actingAs($this->admin)
            ->get(route('erp.quotations.create'))
            ->assertOk()
            ->assertSee('Legacy Single');
    }

    public function test_quotation_client_dropdown_lists_lead_status_clients(): void
    {
        $leadClient = Client::create([
            'business_name' => 'Lead Status Co',
            'status' => 'lead',
            'created_by' => $this->admin->id,
        ]);
        $activeClient = Client::create([
            'business_name' => 'Active Status Co',
            'status' => 'active',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->get(route('erp.quotations.create'))
            ->assertOk()
            ->assertSee('Lead Status Co', false)
            ->assertSee('Active Status Co', false)
            ->assertSee($leadClient->client_number, false);

        $this->actingAs($this->admin)
            ->get(route('erp.invoices.create'))
            ->assertOk()
            ->assertSee('Lead Status Co', false)
            ->assertSee('Active Status Co', false);
    }
}
