<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class CsrPipelineTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private User $admin;
    private User $csr;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);

        $this->admin = $this->createUserWithRole('super_admin');
        $this->csr = $this->createUserWithRole('csr_manager');
    }

    private function makeLead(array $overrides = []): Lead
    {
        return Lead::create(array_merge([
            'source' => 'manual', 'status' => 'new', 'priority' => 'medium',
            'department' => 'GENERAL', 'created_by' => $this->admin->id,
        ], $overrides));
    }

    public function test_pipeline_page_accessible_for_super_admin(): void
    {
        $this->actingAs($this->admin)
            ->get(route('erp.leads.pipeline'))
            ->assertOk()
            ->assertSee('CSR Pipeline')
            ->assertSee('Per-CSR Breakdown');
    }

    public function test_pipeline_page_accessible_for_csr_manager(): void
    {
        $this->actingAs($this->csr)
            ->get(route('erp.leads.pipeline'))
            ->assertOk();
    }

    public function test_pipeline_requires_lead_read_permission(): void
    {
        $noAccess = $this->createUserWithRole('no_such_role');

        $this->actingAs($noAccess)
            ->get(route('erp.leads.pipeline'))
            ->assertStatus(302)
            ->assertSessionHas('permission_denied');
    }

    public function test_pipeline_counts_leads_and_clients(): void
    {
        $this->makeLead(['status' => 'follow_up']);
        $this->makeLead(['status' => 'follow_up']);
        $this->makeLead(['status' => 'quotation']);
        $this->makeLead(['status' => 'lost', 'lost_reason' => 'Price']);

        // Converted WITHOUT invoice
        $clientNoInv = Client::create(['business_name' => 'No Invoice Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        $this->makeLead(['status' => 'converted', 'client_id' => $clientNoInv->id, 'converted_at' => now()]);

        // Converted WITH invoice (partially paid)
        $clientWithInv = Client::create(['business_name' => 'Invoice Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        Invoice::create([
            'invoice_number' => 'INV-PIPE-001', 'client_id' => $clientWithInv->id,
            'currency' => 'USD', 'subtotal' => 500, 'total' => 500,
            'paid_amount' => 200, 'balance' => 300,
            'status' => 'sent', 'payment_status' => 'partially_paid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->admin->id,
        ]);
        $this->makeLead(['status' => 'converted', 'client_id' => $clientWithInv->id, 'converted_at' => now()]);

        // Fully paid client
        $paidClient = Client::create(['business_name' => 'Paid Co', 'status' => 'active', 'created_by' => $this->admin->id]);
        Invoice::create([
            'invoice_number' => 'INV-PIPE-002', 'client_id' => $paidClient->id,
            'currency' => 'USD', 'subtotal' => 100, 'total' => 100,
            'paid_amount' => 100, 'balance' => 0,
            'status' => 'paid', 'payment_status' => 'paid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->admin->id,
        ]);

        $response = $this->actingAs($this->admin)->get(route('erp.leads.pipeline'));
        $response->assertOk();

        $counts = $response->viewData('counts');
        $this->assertEquals(6, $counts['total']);
        $this->assertEquals(3, $counts['clients']);
        $this->assertEquals(2, $counts['followUp']);
        $this->assertEquals(1, $counts['quotation']);
        $this->assertEquals(2, $counts['converted']);
        $this->assertEquals(1, $counts['convertedWithInvoice']);
        $this->assertEquals(1, $counts['lost']);
        $this->assertEquals(1, $counts['partiallyPaid']);
        $this->assertEquals(1, $counts['fullyPaid']);
    }

    public function test_pipeline_shows_per_csr_breakdown(): void
    {
        $this->makeLead(['status' => 'follow_up', 'assigned_to' => $this->csr->id]);
        $this->makeLead(['status' => 'quotation', 'assigned_to' => $this->csr->id]);

        $response = $this->actingAs($this->admin)->get(route('erp.leads.pipeline'));
        $response->assertOk();

        $rows = collect($response->viewData('rows'));
        $row = $rows->firstWhere('user.id', $this->csr->id);

        $this->assertNotNull($row);
        $this->assertEquals(2, $row['total']);
        $this->assertEquals(1, $row['followUp']);
        $this->assertEquals(1, $row['quotation']);
        $this->assertEquals(0, $row['lost']);
    }

    public function test_pipeline_nav_link_present_for_csr(): void
    {
        $this->actingAs($this->csr);

        $nav = collect(erp_navigation());
        $labels = $nav->pluck('label')->filter()->all();

        $this->assertContains('CSR Pipeline', $labels);
    }
}
