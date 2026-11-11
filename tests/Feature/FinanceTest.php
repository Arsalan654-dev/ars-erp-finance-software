<?php

namespace Tests\Feature;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;
use App\Models\Payment;
use App\Models\User;
use App\Services\LedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\InteractsWithRoles;

class FinanceTest extends TestCase
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
            'business_name' => 'Finance Test Client',
            'status' => 'active',
            'created_by' => $admin->id,
            'email' => 'finance@test.com',
        ]);

        LedgerAccount::insert([
            ['code' => '1200', 'name' => 'Cash/Bank', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '1300', 'name' => 'Accounts Receivable', 'type' => 'asset', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => '5000', 'name' => 'Expense', 'type' => 'expense', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function test_invoice_lock_posts_correct_double_entry(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-001',
            'client_id' => $this->client->id,
            'currency' => 'USD',
            'subtotal' => 1000, 'total' => 1000, 'balance' => 1000,
            'status' => 'draft', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->financeUser->id,
        ]);

        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/lock")->assertRedirect();
        $this->assertEquals('locked', $invoice->fresh()->status);

        $arEntry = LedgerEntry::where('invoice_id', $invoice->id)->where('entry_type', 'debit')->first();
        $this->assertNotNull($arEntry);
        $this->assertEquals('1300', $arEntry->ledgerAccount->code);

        $revenueEntry = LedgerEntry::where('invoice_id', $invoice->id)->where('entry_type', 'credit')->first();
        $this->assertNotNull($revenueEntry);
        $this->assertEquals('4000', $revenueEntry->ledgerAccount->code);
    }

    public function test_payment_verify_posts_correct_double_entry(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-002', 'client_id' => $this->client->id,
            'currency' => 'USD', 'subtotal' => 500, 'total' => 500, 'balance' => 500,
            'status' => 'locked', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->financeUser->id,
        ]);
        $payment = Payment::create([
            'payment_number' => 'PAY-TEST-001', 'client_id' => $this->client->id,
            'currency' => 'USD', 'amount' => 500, 'status' => 'pending',
            'payment_date' => now()->toDateString(), 'recorded_by' => $this->financeUser->id,
        ]);
        $payment->invoices()->attach($invoice->id, ['amount' => 500]);

        $this->actingAs($this->financeUser);
        $this->post("/erp/finance/payments/{$payment->id}/acknowledge")->assertRedirect();
        $this->assertEquals('acknowledged', $payment->fresh()->status);
        $this->post("/erp/finance/payments/{$payment->id}/verify")->assertRedirect();
        $this->assertEquals('verified', $payment->fresh()->status);

        $cashEntry = LedgerEntry::where('payment_id', $payment->id)->where('entry_type', 'debit')->first();
        $this->assertNotNull($cashEntry);
        $this->assertEquals('1200', $cashEntry->ledgerAccount->code);

        $arEntry = LedgerEntry::where('payment_id', $payment->id)->where('entry_type', 'credit')->first();
        $this->assertNotNull($arEntry);
        $this->assertEquals('1300', $arEntry->ledgerAccount->code);
    }

    public function test_cannot_verify_already_verified_payment(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-TEST-002', 'client_id' => $this->client->id,
            'currency' => 'USD', 'amount' => 100, 'status' => 'verified',
            'payment_date' => now()->toDateString(), 'recorded_by' => $this->financeUser->id,
        ]);
        $this->actingAs($this->financeUser);
        $this->post("/erp/finance/payments/{$payment->id}/verify")->assertSessionHas('error');
    }

    public function test_expense_approval_posts_correct_double_entry(): void
    {
        $expense = Expense::create([
            'expense_number' => 'EXP-TEST-001', 'category' => 'Office Supplies',
            'currency' => 'PKR', 'amount' => 5000, 'status' => 'pending',
            'expense_date' => now()->toDateString(), 'created_by' => $this->financeUser->id,
        ]);
        $this->actingAs($this->financeUser);
        $this->post("/erp/finance/expenses/{$expense->id}/approve")->assertRedirect();
        $this->assertEquals('approved', $expense->fresh()->status);

        $expenseEntry = LedgerEntry::where('expense_id', $expense->id)->where('entry_type', 'debit')->first();
        $this->assertNotNull($expenseEntry);
        $this->assertEquals('5000', $expenseEntry->ledgerAccount->code);
    }

    public function test_cannot_approve_already_approved_expense(): void
    {
        $expense = Expense::create([
            'expense_number' => 'EXP-TEST-002', 'category' => 'Travel',
            'currency' => 'PKR', 'amount' => 2000, 'status' => 'approved',
            'expense_date' => now()->toDateString(), 'created_by' => $this->financeUser->id,
        ]);
        $this->actingAs($this->financeUser);
        $this->post("/erp/finance/expenses/{$expense->id}/approve")->assertSessionHas('error');
    }

    public function test_missing_ledger_account_throws_exception(): void
    {
        $this->expectException(\RuntimeException::class);
        LedgerService::post('9999', 'debit', 100, 'USD');
    }

    public function test_ledger_entries_are_balanced(): void
    {
        $invoice = Invoice::create([
            'invoice_number' => 'INV-TEST-003', 'client_id' => $this->client->id,
            'currency' => 'USD', 'subtotal' => 750, 'total' => 750, 'balance' => 750,
            'status' => 'draft', 'payment_status' => 'unpaid',
            'issue_date' => now()->toDateString(), 'created_by' => $this->financeUser->id,
        ]);
        $this->actingAs($this->financeUser);
        $this->post("/erp/invoices/{$invoice->id}/lock");

        $debit = LedgerEntry::where('invoice_id', $invoice->id)->where('entry_type', 'debit')->sum('amount');
        $credit = LedgerEntry::where('invoice_id', $invoice->id)->where('entry_type', 'credit')->sum('amount');
        $this->assertEquals($debit, $credit);
    }

    public function test_cannot_reject_already_verified_payment(): void
    {
        $payment = Payment::create([
            'payment_number' => 'PAY-TEST-003', 'client_id' => $this->client->id,
            'currency' => 'USD', 'amount' => 200, 'status' => 'verified',
            'payment_date' => now()->toDateString(), 'recorded_by' => $this->financeUser->id,
        ]);
        $this->actingAs($this->financeUser);
        $this->post("/erp/finance/payments/{$payment->id}/reject")->assertSessionHas('error');
    }

    public function test_cannot_approve_already_approved_expense_2(): void
    {
        $expense = Expense::create([
            'expense_number' => 'EXP-TEST-003', 'category' => 'Software',
            'currency' => 'USD', 'amount' => 300, 'status' => 'approved',
            'expense_date' => now()->toDateString(), 'created_by' => $this->financeUser->id,
        ]);
        $this->actingAs($this->financeUser);
        $this->post("/erp/finance/expenses/{$expense->id}/approve")->assertSessionHas('error');
    }
}
