<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Sequential document numbering (with daily/yearly prefixes).
 *
 * Numbers are derived from the numeric suffix already stored in the number
 * column (NOT from MAX(id)) and are allocated inside a locked transaction so
 * two concurrent creates can never produce the same number.
 */
class NumberSequenceService
{
    /**
     * Generate the next number for a given sequence table.
     * e.g. next('quotations', 'QTN-', 5) => QTN-00042
     *
     * @param  string  $table   table holding the number column
     * @param  string  $column  number column name (defaults to <singular>_number style, pass explicitly)
     */
    public static function next(string $table, string $prefix, int $pad = 5, string $column = ''): string
    {
        $column = $column ?: self::guessColumn($table, $prefix);

        return self::allocate($table, $column, $prefix, $pad);
    }

    /**
     * Next client number: CLI-00001, CLI-00002 …
     */
    public static function nextClientNumber(): string
    {
        return self::allocate('clients', 'client_number', 'CLI-', 5);
    }

    /**
     * Allocate the next sequence number under a row lock with retry.
     */
    private static function allocate(string $table, string $column, string $prefix, int $pad, int $attempts = 5): string
    {
        for ($try = 1; $try <= $attempts; $try++) {
            try {
                return DB::transaction(function () use ($table, $column, $prefix, $pad) {
                    // Lock every existing row of this sequence: serialises concurrent
                    // allocations and lets us compute the real numeric max (mixed
                    // padding widths from seeds/imports break a lexicographic MAX()).
                    $numbers = DB::table($table)
                        ->lockForUpdate()
                        ->where($column, 'like', $prefix.'%')
                        ->pluck($column);

                    $seq = 0;
                    foreach ($numbers as $number) {
                        $seq = max($seq, self::suffix($number, $prefix));
                    }

                    $candidate = $prefix.str_pad((string) ($seq + 1), $pad, '0', STR_PAD_LEFT);

                    // Guard against gaps left by seeded / imported rows.
                    while ($numbers->contains($candidate)) {
                        $seq++;
                        $candidate = $prefix.str_pad((string) ($seq + 1), $pad, '0', STR_PAD_LEFT);
                    }

                    return $candidate;
                });
            } catch (\Throwable $e) {
                if ($try === $attempts) {
                    throw $e;
                }
                usleep(50000 * $try);
            }
        }

        throw new \RuntimeException("Unable to allocate a number for {$table}.{$column}");
    }

    /**
     * Extract the numeric suffix from a stored number (soft-deleted rows included).
     */
    private static function suffix(?string $last, string $prefix): int
    {
        if ($last === null || $last === '') {
            return 0;
        }

        $digits = preg_replace('/\D+/', '', substr($last, strlen($prefix)));

        return $digits === '' ? 0 : (int) $digits;
    }

    private static function guessColumn(string $table, string $prefix): string
    {
        return match ($prefix) {
            'QTN-' => 'quotation_number',
            'INV-' => 'invoice_number',
            'PAY-' => 'payment_number',
            'EXP-' => 'expense_number',
            'TSK-' => 'task_number',
            'PRJ-' => 'project_number',
            'CLI-' => 'client_number',
            default => 'number',
        };
    }
}
