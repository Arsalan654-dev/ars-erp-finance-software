<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Lead;
use App\Models\User;
use App\Services\CsrAssigner;
use App\Services\NumberSequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class LeadPortfolioTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedPermissions();
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);
        $this->admin = $this->createUserWithRole('super_admin');
    }

    private function createCsr(string $name): User
    {
        $csr = $this->createUserWithRole('csr_manager');
        $csr->update(['name' => $name]);

        return $csr;
    }

    // ===== CLIENT NUMBER =====

    public function test_client_number_is_auto_assigned(): void
    {
        $client = Client::create(['business_name' => 'Numbered Co', 'status' => 'active']);

        $this->assertMatchesRegularExpression('/^CLI-\d{5}$/', $client->client_number);
        $this->assertDatabaseHas('clients', ['id' => $client->id, 'client_number' => $client->client_number]);
    }

    public function test_client_numbers_increment(): void
    {
        $first = Client::create(['business_name' => 'First', 'status' => 'active']);
        $second = Client::create(['business_name' => 'Second', 'status' => 'active']);

        $this->assertNotSame($first->client_number, $second->client_number);

        $seqA = (int) substr($first->client_number, 4);
        $seqB = (int) substr($second->client_number, 4);
        $this->assertSame($seqA + 1, $seqB);
    }

    public function test_client_number_skips_existing_seeded_values(): void
    {
        Client::create(['business_name' => 'Seeded', 'status' => 'active', 'client_number' => 'CLI-00099']);
        $next = Client::create(['business_name' => 'After Seed', 'status' => 'active']);

        $this->assertGreaterThan(99, (int) substr($next->client_number, 4));
    }

    // ===== LEAD CAMPAIGN =====

    public function test_lead_campaign_name_stored_and_searchable(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('erp.leads.store'), [
            'business_name' => 'Campaign Client',
            'source' => 'website',
            'campaign_name' => 'Google Ads - Sep 2026',
            'department' => 'US TAX',
            'priority' => 'high',
        ])->assertRedirect();

        $lead = Lead::where('business_name', 'Campaign Client')->firstOrFail();
        $this->assertSame('Google Ads - Sep 2026', $lead->campaign_name);

        $this->get(route('erp.leads.index', ['q' => 'Google Ads']))
            ->assertOk()
            ->assertSee('Campaign Client');
    }

    public function test_lead_form_offers_new_legal_departments(): void
    {
        $this->actingAs($this->admin)
            ->get(route('erp.leads.create'))
            ->assertOk()
            ->assertSee('Legal- US')
            ->assertSee('Legal- UK');
    }

    public function test_lead_accepts_legal_departments(): void
    {
        $this->actingAs($this->admin);

        foreach (['Legal- US', 'Legal- UK'] as $department) {
            $this->post(route('erp.leads.store'), [
                'business_name' => 'Legal Lead '.$department,
                'source' => 'manual',
                'department' => $department,
                'priority' => 'medium',
            ])->assertRedirect();

            $this->assertDatabaseHas('leads', [
                'business_name' => 'Legal Lead '.$department,
                'department' => $department,
            ]);
        }
    }

    public function test_lead_rejects_unknown_department(): void
    {
        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Bad Dept',
                'source' => 'manual',
                'department' => 'ACCOUNTS',
                'priority' => 'medium',
            ])
            ->assertSessionHasErrors('department');
    }

    // ===== PORTFOLIO MATCH (repeat lead) =====

    public function test_repeat_lead_by_email_reuses_existing_client(): void
    {
        $existing = Client::create([
            'business_name' => 'Repeat Co',
            'email' => 'repeat@example.com',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Repeat Co',
                'email' => 'repeat@example.com',
                'source' => 'referral',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $this->assertSame(1, Client::where('email', 'repeat@example.com')->count());

        $lead = Lead::where('email', 'repeat@example.com')->firstOrFail();
        $this->assertSame($existing->id, $lead->client_id);
    }

    public function test_repeat_lead_by_phone_reuses_existing_client(): void
    {
        $existing = Client::create([
            'business_name' => 'Phone Co',
            'phone' => '+1 555 123 4567',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'contact_name' => 'Caller',
                'phone' => '+1 555 123 4567',
                'source' => 'phone',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $lead = Lead::where('phone', '+1 555 123 4567')->firstOrFail();
        $this->assertSame($existing->id, $lead->client_id);
        $this->assertSame(1, Client::where('phone', 'like', '%555 123 4567%')->count());
    }

    public function test_repeat_lead_reuses_previous_csr(): void
    {
        $csr = $this->createCsr('Existing CSR');
        $existing = Client::create([
            'business_name' => 'Same CSR Co',
            'email' => 'samecsr@example.com',
            'status' => 'lead',
        ]);
        Lead::create([
            'client_id' => $existing->id,
            'source' => 'manual',
            'business_name' => 'Same CSR Co',
            'status' => 'contacted',
            'priority' => 'medium',
            'department' => 'GENERAL',
            'assigned_to' => $csr->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Same CSR Co',
                'email' => 'samecsr@example.com',
                'source' => 'manual',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $newLead = Lead::where('email', 'samecsr@example.com')->latest('id')->firstOrFail();
        $this->assertSame($csr->id, $newLead->assigned_to);
    }

    public function test_new_lead_gets_least_loaded_csr(): void
    {
        $csrA = $this->createCsr('CSR A');
        $csrB = $this->createCsr('CSR B');

        // CSR A already carries work; CSR B is idle.
        Lead::create([
            'source' => 'manual',
            'business_name' => 'Load A-1',
            'status' => 'new',
            'priority' => 'medium',
            'department' => 'GENERAL',
            'assigned_to' => $csrA->id,
            'created_by' => $this->admin->id,
        ]);
        Lead::create([
            'source' => 'manual',
            'business_name' => 'Load A-2',
            'status' => 'new',
            'priority' => 'medium',
            'department' => 'GENERAL',
            'assigned_to' => $csrA->id,
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Fresh Lead Co',
                'source' => 'website',
                'department' => 'GENERAL',
                'priority' => 'medium',
            ])
            ->assertRedirect();

        $lead = Lead::where('business_name', 'Fresh Lead Co')->firstOrFail();
        $this->assertSame($csrB->id, $lead->assigned_to);
    }

    public function test_manual_assignment_wins_over_auto_assign(): void
    {
        $csr = $this->createCsr('Manual CSR');
        $other = $this->createCsr('Other CSR');

        $this->actingAs($this->admin)
            ->post(route('erp.leads.store'), [
                'business_name' => 'Manual Pick',
                'source' => 'manual',
                'department' => 'GENERAL',
                'priority' => 'medium',
                'assigned_to' => $other->id,
            ])
            ->assertRedirect();

        $lead = Lead::where('business_name', 'Manual Pick')->firstOrFail();
        $this->assertSame($other->id, $lead->assigned_to);
        $this->assertNotSame($csr->id, $lead->assigned_to);
    }

    // ===== CSR ASSIGNER UNIT =====

    public function test_csr_assigner_finds_client_by_business_name_case_insensitive(): void
    {
        $client = Client::create(['business_name' => 'Acme Ltd', 'status' => 'active']);

        $found = CsrAssigner::findClient(null, null, '  ACME LTD  ');

        $this->assertNotNull($found);
        $this->assertSame($client->id, $found->id);
    }

    public function test_csr_assigner_returns_null_when_no_match(): void
    {
        $this->assertNull(CsrAssigner::findClient('none@none.com', '+1 000 000 0000', 'Nobody Corp'));
    }

    public function test_csr_assigner_pick_returns_role_holder_only(): void
    {
        $this->createCsr('Picked CSR');

        $picked = CsrAssigner::pickCsr();
        $this->assertNotNull($picked);
        $this->assertTrue($picked->roles()->whereIn('name', ['csr_manager', 'team_associate'])->exists());
    }

    // ===== NUMBER SEQUENCE =====

    public function test_number_sequence_parses_existing_number_column(): void
    {
        Client::create(['business_name' => 'Seq One', 'status' => 'active', 'client_number' => 'CLI-00100']);
        $next = NumberSequenceService::nextClientNumber();

        $this->assertSame('CLI-00101', $next);
    }
}
