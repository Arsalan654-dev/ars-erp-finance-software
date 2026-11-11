<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class ConvertPaymentTest extends TestCase
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

    private function makeLead(string $status = 'qualified'): Lead
    {
        return Lead::create([
            'source' => 'whatsapp', 'status' => $status, 'priority' => 'medium',
            'department' => 'GENERAL', 'business_name' => 'Payment Lead',
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_convert_requires_payment_fields(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->admin)
            ->post(route('erp.leads.convert', $lead))
            ->assertSessionHasErrors(['payment_type', 'payment_currency']);

        $this->assertEquals('qualified', $lead->fresh()->status);
    }

    public function test_convert_requires_screenshot_for_full_payment(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->admin)->post(route('erp.leads.convert', $lead), [
            'payment_type' => 'full',
            'payment_currency' => 'USD',
            'payment_amount' => 100,
        ])->assertSessionHasErrors('payment_screenshot');

        $this->assertEquals('qualified', $lead->fresh()->status);
    }

    public function test_convert_full_payment_creates_pending_payment(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->admin)->post(route('erp.leads.convert', $lead), [
            'payment_type' => 'full',
            'payment_currency' => 'USD',
            'payment_amount' => 750,
            'payment_method' => 'bank',
            'payment_reference' => 'TXN-9911',
            'payment_date' => now()->toDateString(),
            'payment_screenshot' => UploadedFile::fake()->create('pay.png', 50, 'image/png'),
            'screenshot_amount' => 750,
        ])->assertRedirect();

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);
        $this->assertEquals('full', $lead->payment_type);
        $this->assertNotNull($lead->payment_screenshot_path);
        $this->assertNotNull($lead->client_id);

        $payment = \App\Models\Payment::where('lead_id', $lead->id)->first();
        $this->assertNotNull($payment);
        $this->assertEquals('pending', $payment->status);
        $this->assertEquals(750, (float) $payment->amount);
        $this->assertEquals('USD', $payment->currency);
        $this->assertEquals($lead->client_id, $payment->client_id);
        $this->assertEquals($this->admin->id, $payment->recorded_by);
        $this->assertEquals($lead->payment_screenshot_path, $payment->screenshot_path);
    }

    public function test_convert_special_approval_exempt_from_screenshot(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->admin)->post(route('erp.leads.convert', $lead), [
            'payment_type' => 'special',
            'payment_currency' => 'GBP',
            'special_approval_note' => 'Approved by CEO — waived first invoice.',
        ])->assertRedirect();

        $lead->refresh();
        $this->assertEquals('converted', $lead->status);
        $this->assertEquals('special', $lead->payment_type);
        $this->assertNull($lead->payment_screenshot_path);
        $this->assertNotNull($lead->payment_approval_note);
        $this->assertEquals($this->admin->id, $lead->payment_approved_by);
        $this->assertNull($lead->payments()->first());
    }

    public function test_convert_special_approval_requires_note(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->admin)->post(route('erp.leads.convert', $lead), [
            'payment_type' => 'special',
            'payment_currency' => 'USD',
        ])->assertSessionHasErrors('special_approval_note');

        $this->assertEquals('qualified', $lead->fresh()->status);
    }

    public function test_convert_special_approval_blocked_for_csr(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->csr)->post(route('erp.leads.convert', $lead), [
            'payment_type' => 'special',
            'payment_currency' => 'USD',
            'special_approval_note' => 'Trying to bypass screenshot.',
        ])->assertSessionHas('error');

        $this->assertEquals('qualified', $lead->fresh()->status);
    }

    public function test_transition_to_converted_is_blocked(): void
    {
        $lead = $this->makeLead('quotation');

        $this->actingAs($this->admin)->post(route('erp.leads.transition', $lead), [
            'status' => 'converted',
        ])->assertSessionHas('error');

        $this->assertEquals('quotation', $lead->fresh()->status);
    }

    public function test_payment_screenshot_is_downloadable(): void
    {
        $lead = $this->makeLead();

        $this->actingAs($this->admin)->post(route('erp.leads.convert', $lead), [
            'payment_type' => 'partial',
            'payment_currency' => 'EUR',
            'payment_amount' => 250,
            'payment_screenshot' => UploadedFile::fake()->create('shot.jpg', 20, 'image/jpeg'),
            'screenshot_amount' => 250,
        ])->assertRedirect();

        $this->actingAs($this->admin)
            ->get(route('erp.leads.payment-screenshot', $lead->fresh()))
            ->assertOk();
    }
}
