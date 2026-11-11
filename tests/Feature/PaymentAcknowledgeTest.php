<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\InteractsWithRoles;
use Tests\TestCase;

class PaymentAcknowledgeTest extends TestCase
{
    use RefreshDatabase, InteractsWithRoles;

    private User $financeUser;
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\SessionSecurity::class);

        $this->financeUser = $this->createUserWithRole('finance');
        $admin = $this->createUserWithRole('super_admin');
        $this->client = Client::create([
            'business_name' => 'Ack Test Client', 'status' => 'active',
            'created_by' => $admin->id, 'email' => 'ack@test.com',
        ]);

        LedgerAccount::insert([
            ['code' => '1200', 'name' => 'Cash/Bank', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    private function makePayment(string $status = 'pending'): Payment
    {
        return Payment::create([
            'payment_number' => 'PAY-ACK-'.uniqid(),
            'client_id' => $this->client->id,
            'currency' => 'USD', 'amount' => 100, 'status' => $status,
            'payment_date' => now()->toDateString(), 'recorded_by' => $this->financeUser->id,
        ]);
    }

    public function test_acknowledge_pending_payment(): void
    {
        $payment = $this->makePayment('pending');

        $this->actingAs($this->financeUser)
            ->post("/erp/finance/payments/{$payment->id}/acknowledge")
            ->assertRedirect();

        $payment->refresh();
        $this->assertEquals('acknowledged', $payment->status);
        $this->assertEquals($this->financeUser->id, $payment->acknowledged_by);
        $this->assertNotNull($payment->acknowledged_at);
    }

    public function test_cannot_verify_pending_payment_strict_flow(): void
    {
        $payment = $this->makePayment('pending');

        $this->actingAs($this->financeUser)
            ->post("/erp/finance/payments/{$payment->id}/verify")
            ->assertSessionHas('error');

        $this->assertEquals('pending', $payment->fresh()->status);
        $this->assertEquals(0, LedgerEntry::where('payment_id', $payment->id)->count());
    }

    public function test_acknowledge_then_verify_flow(): void
    {
        $payment = $this->makePayment('pending');

        $this->actingAs($this->financeUser)->post("/erp/finance/payments/{$payment->id}/acknowledge");
        $this->assertEquals('acknowledged', $payment->fresh()->status);

        $this->actingAs($this->financeUser)
            ->post("/erp/finance/payments/{$payment->id}/verify")
            ->assertRedirect();

        $this->assertEquals('verified', $payment->fresh()->status);

        $debit = LedgerEntry::where('payment_id', $payment->id)->where('entry_type', 'debit')->first();
        $credit = LedgerEntry::where('payment_id', $payment->id)->where('entry_type', 'credit')->first();
        $this->assertNotNull($debit);
        $this->assertNotNull($credit);
        $this->assertEquals('1200', $debit->ledgerAccount->code);
        $this->assertEquals('1300', $credit->ledgerAccount->code);
    }

    public function test_cannot_acknowledge_non_pending_payment(): void
    {
        $payment = $this->makePayment('verified');

        $this->actingAs($this->financeUser)
            ->post("/erp/finance/payments/{$payment->id}/acknowledge")
            ->assertSessionHas('error');

        $this->assertEquals('verified', $payment->fresh()->status);
    }

    public function test_cannot_acknowledge_twice(): void
    {
        $payment = $this->makePayment('acknowledged');

        $this->actingAs($this->financeUser)
            ->post("/erp/finance/payments/{$payment->id}/acknowledge")
            ->assertSessionHas('error');

        $this->assertEquals('acknowledged', $payment->fresh()->status);
    }

    public function test_can_reject_acknowledged_payment(): void
    {
        $payment = $this->makePayment('acknowledged');

        $this->actingAs($this->financeUser)
            ->post("/erp/finance/payments/{$payment->id}/reject")
            ->assertRedirect();

        $this->assertEquals('rejected', $payment->fresh()->status);
    }

    public function test_csr_manager_cannot_access_finance_payment_routes(): void
    {
        $csr = $this->createUserWithRole('csr_manager');
        $payment = $this->makePayment('pending');

        // App handler converts 403 into a friendly redirect (see App\Exceptions\Handler)
        $this->actingAs($csr)->get('/erp/finance/payments')
            ->assertStatus(302)
            ->assertSessionHas('permission_denied');
        $this->actingAs($csr)->post("/erp/finance/payments/{$payment->id}/acknowledge")
            ->assertStatus(302)
            ->assertSessionHas('permission_denied');

        $this->assertEquals('pending', $payment->fresh()->status);
    }
}
