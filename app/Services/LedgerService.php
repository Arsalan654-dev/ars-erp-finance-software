<?php

namespace App\Services;

use App\Models\ExchangeRate;
use App\Models\LedgerAccount;
use App\Models\LedgerEntry;

/**
 * Simple finance / ledger engine:
 *  - exchange rate lookups (manual / API-ready)
 *  - PKR conversion
 *  - ledger entry posting for revenue & expenses
 */
class LedgerService
{
    public const BASE_CURRENCY = 'PKR';

    /**
     * Get the rate for a currency → PKR. Falls back to configurable defaults.
     */
    public static function rateToPkr(string $fromCurrency, ?string $date = null): ?float
    {
        $query = ExchangeRate::where('from_currency', $fromCurrency)->where('to_currency', self::BASE_CURRENCY);

        if ($date) {
            $query->where('date', '<=', $date);
        }

        $rate = $query->orderByDesc('date')->first();

        if ($rate) {
            return (float) $rate->rate;
        }

        // Fallback defaults from config
        return config("erp.default_exchange_rates.{$fromCurrency}");
    }

    /**
     * Convert an amount into PKR at a given date's rate.
     */
    public static function toPkr(string $currency, float $amount, ?string $date = null): array
    {
        if ($currency === self::BASE_CURRENCY) {
            return ['amount' => round($amount, 2), 'rate' => 1.0];
        }

        $rate = self::rateToPkr($currency, $date);
        if (! $rate) {
            return ['amount' => null, 'rate' => null];
        }

        return ['amount' => round($amount * $rate, 2), 'rate' => $rate];
    }

    /**
     * Post a ledger entry (debit or credit).
     *
     * @throws \RuntimeException if the ledger account does not exist
     */
    public static function post(
        string $accountCode,
        string $entryType,
        float $amount,
        string $currency = self::BASE_CURRENCY,
        ?array $relations = null,
        ?string $description = null,
        ?string $entryDate = null
    ): LedgerEntry {
        $account = LedgerAccount::where('code', $accountCode)->first();
        if (! $account) {
            throw new \RuntimeException("Ledger account '{$accountCode}' not found. Transaction rolled back.");
        }

        $relations = $relations ?? [];

        return LedgerEntry::create([
            'ledger_account_id' => $account->id,
            'entry_type' => $entryType,
            'amount' => $amount,
            'currency' => $currency,
            'invoice_id' => $relations['invoice_id'] ?? null,
            'payment_id' => $relations['payment_id'] ?? null,
            'expense_id' => $relations['expense_id'] ?? null,
            'description' => $description,
            'entry_date' => $entryDate ?? now()->toDateString(),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * Revenue balance sheet: revenue and expenses in PKR.
     */
    public static function balanceSheet(?string $from = null, ?string $to = null): array
    {
        $query = LedgerEntry::query();

        if ($from) {
            $query->whereDate('entry_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('entry_date', '<=', $to);
        }

        $entries = $query->with('ledgerAccount')->get();

        $revenue = 0.0;
        $expenses = 0.0;

        foreach ($entries as $entry) {
            if (! $entry->ledgerAccount) {
                continue;
            }

            $amount = $entry->currency === self::BASE_CURRENCY
                ? (float) $entry->amount
                : (float) ($entry->amount * (self::rateToPkr($entry->currency) ?? 1));

            $isIncome = in_array($entry->ledgerAccount->type, ['income', 'revenue']);
            $isExpense = $entry->ledgerAccount->type === 'expense';

            if ($isIncome && $entry->entry_type === 'credit') {
                $revenue += $amount;
            }
            if ($isExpense && $entry->entry_type === 'debit') {
                $expenses += $amount;
            }
        }

        return [
            'revenue' => round($revenue, 2),
            'expenses' => round($expenses, 2),
            'net' => round($revenue - $expenses, 2),
        ];
    }
}