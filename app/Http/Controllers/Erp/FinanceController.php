<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\BankVerification;
use App\Models\Expense;
use App\Models\Payment;
use App\Services\AuditService;
use App\Services\LedgerService;
use App\Services\NumberSequenceService;
use App\Services\PdfService;
use Illuminate\Http\Request;

class FinanceController extends Controller
{
    // ---- Payments ----

    public function payments(Request $request)
    {
        $query = Payment::with('client', 'recorder');

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }
        if ($request->get('flagged') === '1') {
            $query->where('amount_mismatch', true);
        }
        if ($request->get('q')) {
            $q = $request->get('q');
            $query->where(function ($query) use ($q): void {
                $query->where('payment_number', 'like', '%'.$q.'%')
                    ->orWhere('reference_number', 'like', '%'.$q.'%');
            });
        }

        $payments = $query->latest()->paginate(15)->withQueryString();

        $flaggedCount = Payment::where('amount_mismatch', true)->count();

        return view('erp.finance.payments.index', compact('payments', 'flaggedCount'));
    }

    public function createPayment()
    {
        $clients = \App\Models\Client::where('status', 'active')->orderBy('business_name')->get();

        return view('erp.finance.payments.create', compact('clients'));
    }

    public function storePayment(Request $request)
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'currency' => ['required', 'in:USD,GBP,EUR,PKR'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'screenshot_amount' => ['required', 'numeric', 'min:0'],
            'screenshot' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'payment_method' => ['nullable', 'string'],
            'reference_number' => ['nullable', 'string'],
            'payment_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'invoice_id' => ['nullable', 'exists:invoices,id'],
        ]);

        $data['payment_number'] = NumberSequenceService::next('payments', 'PAY-');
        $data['status'] = 'pending';
        $data['recorded_by'] = auth()->id();

        $pkr = LedgerService::toPkr($data['currency'], $data['amount']);
        $data['pkr_equivalent'] = $pkr['amount'];
        $data['exchange_rate'] = $pkr['rate'];

        // Screenshot is mandatory — store on the private disk.
        $data['screenshot_path'] = $request->file('screenshot')->store('payment-screenshots', 'private');

        // Mismatch detection: entered amount vs the amount visible on the screenshot.
        $mismatch = round((float) $data['amount'], 2) !== round((float) $data['screenshot_amount'], 2);
        $data['amount_mismatch'] = $mismatch;

        unset($data['screenshot'], $data['invoice_id']);

        $payment = Payment::create($data);

        // Link to invoice if provided (do NOT update invoice status yet — only on verify)
        if ($request->filled('invoice_id')) {
            $invoice = \App\Models\Invoice::findOrFail($request->invoice_id);
            $payment->invoices()->attach($invoice->id, ['amount' => $request->input('amount')]);
        }

        AuditService::log('PAYMENT_CREATE', 'payment', $payment->id, [
            'amount' => $request->input('amount'),
            'currency' => $data['currency'],
            'screenshot_amount' => $data['screenshot_amount'],
            'amount_mismatch' => $mismatch,
        ]);

        // Admin sees EVERY payment — and gets a direct alert when the
        // entered amount does not match the uploaded screenshot.
        if ($mismatch) {
            AuditService::log('PAYMENT_AMOUNT_MISMATCH', 'payment', $payment->id, [
                'entered' => (float) $request->input('amount'),
                'screenshot' => (float) $data['screenshot_amount'],
                'recorded_by' => auth()->id(),
            ]);
            \App\Services\AdminNotifier::paymentMismatch(
                $payment,
                (float) $request->input('amount'),
                (float) $data['screenshot_amount'],
                auth()->id()
            );
        }

        \App\Services\AdminNotifier::notify(
            'payment_recorded',
            'Payment recorded: '.$payment->payment_number,
            sprintf(
                '%s %s from %s recorded by %s.%s',
                $data['currency'],
                number_format((float) $request->input('amount'), 2),
                $payment->client?->business_name ?? 'client',
                auth()->user()->name ?? 'system',
                $mismatch ? ' FLAGGED: amount does not match the screenshot.' : ''
            ),
            '/erp/finance/payments/'.$payment->id,
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.finance.payments.show', $payment)
            ->with('success', 'Payment recorded.'.($mismatch ? ' ⚠ Amount does not match the screenshot — admin has been notified.' : ''));
    }

    public function showPayment(Payment $payment)
    {
        $payment->load(['client', 'invoices', 'bankVerification', 'recorder', 'verifier', 'acknowledger', 'lead']);

        return view('erp.finance.payments.show', compact('payment'));
    }

    public function acknowledgePayment(Request $request, Payment $payment)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

                if ($payment->status !== 'pending') {
                    throw new \RuntimeException('Only pending payments can be acknowledged.');
                }

                $payment->update([
                    'status' => 'acknowledged',
                    'acknowledged_by' => auth()->id(),
                    'acknowledged_at' => now(),
                ]);

                AuditService::log('PAYMENT_ACKNOWLEDGE', 'payment', $payment->id, [
                    'payment_number' => $payment->payment_number,
                    'acknowledged_by' => auth()->id(),
                    'recorded_by' => $payment->recorded_by,
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        \App\Services\AdminNotifier::notify(
            'payment_acknowledged',
            'Payment acknowledged: '.$payment->payment_number,
            auth()->user()->name.' acknowledged '.($payment->currency ?? '').' '.number_format((float) $payment->amount, 2).' — awaiting verification.',
            '/erp/finance/payments/'.$payment->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Payment acknowledged. Awaiting verification.');
    }

    public function verifyPayment(Request $request, Payment $payment)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                // Lock row to prevent double-verification race condition
                $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

                // Idempotency guard (now inside lock)
                if ($payment->status === 'verified') {
                    throw new \RuntimeException('Payment is already verified.');
                }

                if ($payment->status === 'rejected') {
                    throw new \RuntimeException('Cannot verify a rejected payment.');
                }

                if ($payment->status !== 'acknowledged') {
                    throw new \RuntimeException('Payment must be acknowledged before verification.');
                }

                $payment->update([
                    'status' => 'verified',
                    'verified_by' => auth()->id(),
                    'verified_at' => now(),
                ]);

                // Update linked invoices — only now that payment is verified
                foreach ($payment->invoices as $invoice) {
                    $pivotAmount = $invoice->pivot->amount ?? 0;
                    $newPaid = $invoice->paid_amount + $pivotAmount;
                    $newBalance = $invoice->total - $newPaid;
                    $paymentStatus = $newBalance <= 0 ? 'paid' : 'partially_paid';
                    $invoice->update([
                        'paid_amount' => $newPaid,
                        'balance' => max(0, $newBalance),
                        'payment_status' => $paymentStatus,
                        'status' => $paymentStatus === 'paid' ? 'paid' : $invoice->status,
                    ]);
                }

                // Double-entry ledger:
                //   DR 1200 Cash/Bank
                //   CR 1300 Accounts Receivable
                $amount = $payment->pkr_equivalent ?: $payment->amount;
                LedgerService::post('1200', 'debit', $amount, 'PKR', [
                    'payment_id' => $payment->id,
                ], "Payment {$payment->payment_number} — cash received");
                LedgerService::post('1300', 'credit', $amount, 'PKR', [
                    'payment_id' => $payment->id,
                ], "Payment {$payment->payment_number} — AR settled");

                \App\Services\EventNotifier::paymentReceived($payment);

                AuditService::log('PAYMENT_VERIFY', 'payment', $payment->id);

                if ($payment->bankVerification) {
                    $payment->bankVerification->update(['status' => 'verified', 'verified_by' => auth()->id(), 'verified_at' => now()]);
                }
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Payment verification failed: ' . $e->getMessage());
        }

        \App\Services\AdminNotifier::notify(
            'payment_verified',
            'Payment verified: '.$payment->payment_number,
            auth()->user()->name.' verified '.($payment->currency ?? '').' '.number_format((float) $payment->amount, 2).' for '.($payment->client?->business_name ?? 'client').'.',
            '/erp/finance/payments/'.$payment->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Payment verified.');
    }

    public function rejectPayment(Request $request, Payment $payment)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($payment) {
                $payment = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();

                // State validation
                if ($payment->status === 'verified') {
                    throw new \RuntimeException('Cannot reject a verified payment.');
                }
                if ($payment->status === 'rejected') {
                    throw new \RuntimeException('Payment is already rejected.');
                }

                $payment->update(['status' => 'rejected']);

                AuditService::log('PAYMENT_REJECT', 'payment', $payment->id, [
                    'payment_number' => $payment->payment_number,
                    'amount' => $payment->amount,
                    'rejected_by' => auth()->id(),
                ]);
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        \App\Services\AdminNotifier::notify(
            'payment_rejected',
            'Payment rejected: '.$payment->payment_number,
            auth()->user()->name.' rejected '.($payment->currency ?? '').' '.number_format((float) $payment->amount, 2).' for '.($payment->client?->business_name ?? 'client').'.',
            '/erp/finance/payments/'.$payment->id,
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Payment rejected.');
    }

    public function destroyPayment(Payment $payment)
    {
        if (! auth()->user()?->isSuperAdmin()) {
            return back()->with('error', 'Only a super admin can delete payment records.');
        }

        if ($payment->status === 'verified') {
            return back()->with('error', 'Verified payments cannot be deleted — the ledger is already posted.');
        }

        $payment->invoices()->detach();
        $payment->delete();

        AuditService::log('PAYMENT_DELETE', 'payment', $payment->id, [
            'payment_number' => $payment->payment_number,
            'amount' => $payment->amount,
            'deleted_by' => auth()->id(),
        ]);

        \App\Services\AdminNotifier::notify(
            'payment_deleted',
            'Payment deleted: '.$payment->payment_number,
            auth()->user()->name.' deleted payment '.($payment->currency ?? '').' '.number_format((float) $payment->amount, 2).'.',
            '/erp/finance/payments',
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.finance.payments')->with('success', 'Payment deleted.');
    }

    public function paymentReceipt(Payment $payment)
    {
        return PdfService::receipt($payment)->stream("receipt-{$payment->payment_number}.pdf");
    }

    public function paymentScreenshot(Payment $payment)
    {
        abort_unless($payment->screenshot_path, 404);

        $disk = \Illuminate\Support\Facades\Storage::disk('private');
        abort_unless($disk->exists($payment->screenshot_path), 404);

        return $disk->response($payment->screenshot_path, 'payment-screenshot-'.basename($payment->screenshot_path));
    }

    // ---- Bank Verification ----

    public function bankVerifications(Request $request)
    {
        $query = BankVerification::with('payment.client');

        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        $verifications = $query->latest()->paginate(15)->withQueryString();

        return view('erp.finance.bank-verifications.index', compact('verifications'));
    }

    public function storeBankVerification(Request $request, Payment $payment)
    {
        $data = $request->validate([
            'bank_name' => ['nullable', 'string'],
            'account_number' => ['nullable', 'string'],
            'transaction_reference' => ['nullable', 'string'],
        ]);

        $payment->bankVerification()->create($data);

        return back()->with('success', 'Bank verification record added.');
    }

    // ---- Expenses ----

    public function expenses(Request $request)
    {
        $query = Expense::with('creator');

        if ($request->get('category')) {
            $query->where('category', $request->get('category'));
        }
        if ($request->get('status')) {
            $query->where('status', $request->get('status'));
        }

        $expenses = $query->latest()->paginate(15)->withQueryString();

        return view('erp.finance.expenses.index', compact('expenses'));
    }

    public function createExpense()
    {
        return view('erp.finance.expenses.create');
    }

    public function storeExpense(Request $request)
    {
        $data = $request->validate([
            'category' => ['nullable', 'string'],
            'currency' => ['required', 'in:PKR,USD,GBP,EUR'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_date' => ['nullable', 'date'],
            'description' => ['nullable', 'string'],
        ]);

        $data['expense_number'] = NumberSequenceService::next('expenses', 'EXP-');
        $data['status'] = 'pending';
        $data['created_by'] = auth()->id();

        $pkr = LedgerService::toPkr($data['currency'], $data['amount']);
        $data['pkr_equivalent'] = $pkr['amount'];
        $data['exchange_rate'] = $pkr['rate'];

        $expense = Expense::create($data);

        AuditService::log('EXPENSE_CREATE', 'expense', $expense->id);

        \App\Services\AdminNotifier::notify(
            'expense_recorded',
            'Expense recorded: '.$expense->expense_number,
            auth()->user()->name.' recorded expense '.($expense->currency ?? '').' '.number_format((float) $expense->amount, 2).' ('.($expense->category ?? 'n/a').').',
            '/erp/finance/expenses',
            ['super_admin'],
            auth()->id()
        );

        return redirect()->route('erp.finance.expenses.index')->with('success', 'Expense recorded.');
    }

    public function approveExpense(Expense $expense)
    {
        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($expense) {
                // Lock row to prevent double-approval race condition
                $expense = Expense::whereKey($expense->id)->lockForUpdate()->firstOrFail();

                // Idempotency guard (now inside lock)
                if ($expense->status === 'approved') {
                    throw new \RuntimeException('Expense is already approved.');
                }

                $expense->update(['status' => 'approved', 'approved_by' => auth()->id(), 'approved_at' => now()]);

                // Double-entry ledger: Debit 5000 (Expense), Credit 1200 (Cash/Bank)
                $amount = $expense->pkr_equivalent ?: $expense->amount;
                LedgerService::post('5000', 'debit', $amount, 'PKR', [
                    'expense_id' => $expense->id,
                ], "Expense {$expense->expense_number} approved");
                LedgerService::post('1200', 'credit', $amount, 'PKR', [
                    'expense_id' => $expense->id,
                ], "Expense {$expense->expense_number} — cash paid out");
            });
        } catch (\RuntimeException $e) {
            return back()->with('error', 'Expense approval failed: ' . $e->getMessage());
        }

        \App\Services\AdminNotifier::notify(
            'expense_approved',
            'Expense approved: '.$expense->expense_number,
            auth()->user()->name.' approved expense '.($expense->currency ?? '').' '.number_format((float) $expense->amount, 2).' ('.($expense->category ?? 'n/a').').',
            '/erp/finance/expenses',
            ['super_admin'],
            auth()->id()
        );

        return back()->with('success', 'Expense approved.');
    }

    // ---- Ledger ----

    public function ledger(Request $request)
    {
        $query = \App\Models\LedgerEntry::with('ledgerAccount', 'invoice', 'payment', 'expense');

        if ($request->get('from')) {
            $query->whereDate('entry_date', '>=', $request->get('from'));
        }
        if ($request->get('to')) {
            $query->whereDate('entry_date', '<=', $request->get('to'));
        }

        $entries = $query->latest()->paginate(20)->withQueryString();
        $summary = LedgerService::balanceSheet($request->get('from'), $request->get('to'));

        return view('erp.finance.ledger.index', compact('entries', 'summary'));
    }

    // ---- Financial Reports ----

    public function reports(Request $request)
    {
        $from = $request->get('from');
        $to = $request->get('to');

        $iconTrendUp = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>';
        $iconShield = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        $iconChart = '<svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>';

        // P&L
        $summary = LedgerService::balanceSheet($from, $to);

        // AR Aging (open invoices grouped by overdue buckets)
        // diffInDays with false: positive = due_date in future, negative = overdue
        $invoices = \App\Models\Invoice::with('client')->where('payment_status', '!=', 'paid')->get();
        $aging = ['current' => 0, '0_30' => 0, '31_60' => 0, '61_90' => 0, '90_plus' => 0];
        $agingCount = $aging;
        foreach ($invoices as $inv) {
            $days = $inv->due_date ? now()->startOfDay()->diffInDays($inv->due_date, false) : 0;
            $amt = $inv->balance ?? 0;

            if ($days >= 0) {
                // Due date is in the future or today — Current
                $bucket = 'current';
            } else {
                // Overdue — bucket by absolute days overdue
                $overdueDays = abs($days);
                $bucket = $overdueDays <= 30 ? '0_30' : ($overdueDays <= 60 ? '31_60' : ($overdueDays <= 90 ? '61_90' : '90_plus'));
            }

            $aging[$bucket] += $amt;
            $agingCount[$bucket]++;
        }

        // Cash flow: monthly net for last 6 months
        $cashflow = collect(range(5, 0))->map(function ($i) {
            $month = now()->startOfMonth()->subMonths($i);
            $from = $month->copy()->startOfMonth();
            $to = $month->copy()->endOfMonth();
            $s = LedgerService::balanceSheet($from->toDateString(), $to->toDateString());

            return ['label' => $month->format('M Y'), 'revenue' => $s['revenue'], 'expenses' => $s['expenses'], 'net' => $s['net']];
        });

        // Flagged payments — entered amount does not match the screenshot (fraud/mismatch report)
        $flagged = Payment::with(['client', 'recorder'])
            ->where('amount_mismatch', true)
            ->latest()
            ->get();

        return view('erp.finance.reports.index', compact('summary', 'aging', 'agingCount', 'cashflow', 'from', 'to', 'flagged', 'iconTrendUp', 'iconShield', 'iconChart'));
    }

    // ---- Exchange Rate Management ----

    public function exchangeRates(Request $request)
    {
        $rates = \App\Models\ExchangeRate::with('setter')->latest()->paginate(20)->withQueryString();
        $defaults = config('erp.default_exchange_rates', []);

        return view('erp.finance.exchange-rates.index', compact('rates', 'defaults'));
    }

    public function storeExchangeRate(Request $request)
    {
        $data = $request->validate([
            'from_currency' => ['required', 'in:USD,GBP,EUR'],
            'rate' => ['required', 'numeric', 'min:0.0001'],
            'date' => ['nullable', 'date'],
        ]);

        \App\Models\ExchangeRate::create([
            'from_currency' => $data['from_currency'],
            'to_currency' => 'PKR',
            'rate' => $data['rate'],
            'date' => $data['date'] ?? now()->toDateString(),
            'source' => 'manual',
            'set_by' => auth()->id(),
        ]);

        AuditService::log('EXCHANGE_RATE_SET', 'exchange_rate', null, [
            'from' => $data['from_currency'], 'to' => 'PKR', 'rate' => $data['rate'], 'date' => $data['date'] ?? now()->toDateString(),
        ]);

        return back()->with('success', "Exchange rate saved: 1 {$data['from_currency']} = {$data['rate']} PKR");
    }

    public function destroyExchangeRate(\App\Models\ExchangeRate $exchangeRate)
    {
        $exchangeRate->delete();

        return back()->with('success', 'Exchange rate removed.');
    }
}