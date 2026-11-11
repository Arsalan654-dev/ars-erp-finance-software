<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Invoice;
use App\Services\AuditService;
use App\Services\SensitiveDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Legacy data migration CLI.
 *
 * Expected input: a CSV with header row. The file is processed in chunks
 * with validation, sanitization and a final report.
 *
 * CSV columns (clients):
 *   business_name,legal_name,email,phone,country,address,ein,itin,utr,ssn,tax_type,status
 *
 * CSV columns (invoices):
 *   client_email,business_name,invoice_number,currency,amount,issue_date,due_date,status
 *   (historical invoices should use status LEGACY_PAID)
 *
 * Usage:
 *   php artisan erp:migrate-clients path/to/clients.csv
 *   php artisan erp:migrate-invoices path/to/invoices.csv
 */
class MigrateLegacyData extends Command
{
    protected $signature = 'erp:migrate {--type=clients : clients|invoices} {file : Path to CSV file} {--chunk=100 : Rows per chunk}';

    protected $description = 'Migrate legacy client/invoice data from CSV into the ERP';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! file_exists($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $type = $this->option('type');
        $chunk = (int) $this->option('chunk');

        $rows = $this->readCsv($file);
        $this->info("Loaded ".count($rows)." rows from {$file}");

        $result = $type === 'invoices'
            ? $this->migrateInvoices($rows, $chunk)
            : $this->migrateClients($rows, $chunk);

        $this->table(['Metric', 'Count'], $result['summary']);

        return $result['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }

    private function migrateClients(array $rows, int $chunk): array
    {
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach (array_chunk($rows, $chunk) as $batch) {
            DB::beginTransaction();
            try {
                foreach ($batch as $row) {
                    $businessName = trim($row['business_name'] ?? '');
                    if (! $businessName) {
                        $skipped++;
                        continue;
                    }

                    // Skip if duplicate business name + email exists
                    $exists = Client::where('business_name', $businessName)
                        ->when($row['email'] ?? null, fn ($q, $email) => $q->where('email', $email))
                        ->exists();

                    if ($exists) {
                        $skipped++;
                        continue;
                    }

                    Client::create([
                        'business_name' => $businessName,
                        'legal_name' => $row['legal_name'] ?? null,
                        'email' => $row['email'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'country' => $row['country'] ?? null,
                        'address' => $row['address'] ?? null,
                        'ein' => $row['ein'] ?? null,
                        'itin' => $row['itin'] ?? null,
                        'utr' => $row['utr'] ?? null,
                        'ssn' => $row['ssn'] ?? null,
                        'tax_type' => $row['tax_type'] ?? null,
                        'status' => in_array($row['status'] ?? '', ['active', 'inactive', 'lead']) ? $row['status'] : 'active',
                    ]);
                    $created++;
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors += count($batch);
                $this->error('Batch failed: '.$e->getMessage());
            }
        }

        return [
            'summary' => [
                ['Created', $created],
                ['Skipped (duplicates/missing name)', $skipped],
                ['Errors', $errors],
            ],
            'errors' => $errors,
        ];
    }

    private function migrateInvoices(array $rows, int $chunk): array
    {
        $created = 0;
        $skipped = 0;
        $errors = 0;

        foreach (array_chunk($rows, $chunk) as $batch) {
            DB::beginTransaction();
            try {
                foreach ($batch as $row) {
                    $number = trim($row['invoice_number'] ?? '');
                    if (! $number) {
                        $skipped++;
                        continue;
                    }

                    if (Invoice::where('invoice_number', $number)->exists()) {
                        $skipped++;
                        continue;
                    }

                    // Find client by email or business name
                    $client = null;
                    if (! empty($row['client_email'])) {
                        $client = Client::where('email', $row['client_email'])->first();
                    }
                    if (! $client && ! empty($row['business_name'])) {
                        $client = Client::where('business_name', $row['business_name'])->first();
                    }

                    if (! $client) {
                        $skipped++;
                        continue;
                    }

                    $amount = (float) ($row['amount'] ?? 0);
                    $currency = strtoupper((string) ($row['currency'] ?? 'USD'));

                    // Historical invoices are imported as LEGACY_PAID so current
                    // revenue accounting is not distorted.
                    $legacyStatus = strtoupper((string) ($row['status'] ?? ''));
                    $status = $legacyStatus === 'LEGACY_PAID' ? 'paid' : ($legacyStatus === 'LEGACY_UNPAID' ? 'sent' : 'sent');
                    $paymentStatus = $legacyStatus === 'LEGACY_PAID' ? 'paid' : 'unpaid';

                    $invoice = Invoice::create([
                        'invoice_number' => $number,
                        'client_id' => $client->id,
                        'currency' => $currency,
                        'subtotal' => $amount,
                        'total' => $amount,
                        'paid_amount' => $status === 'paid' ? $amount : 0,
                        'balance' => $status === 'paid' ? 0 : $amount,
                        'status' => $status,
                        'payment_status' => $paymentStatus,
                        'issue_date' => $row['issue_date'] ?? now()->toDateString(),
                        'due_date' => $row['due_date'] ?? null,
                        'created_by' => auth()->id(),
                    ]);

                    $invoice->items()->create([
                        'description' => 'Legacy invoice import',
                        'quantity' => 1,
                        'unit_price' => $amount,
                        'total' => $amount,
                    ]);

                    $created++;
                }
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                $errors += count($batch);
                $this->error('Batch failed: '.$e->getMessage());
            }
        }

        AuditService::log('DATA_MIGRATION', null, null, ['type' => 'invoices', 'created' => $created]);

        return [
            'summary' => [
                ['Created', $created],
                ['Skipped', $skipped],
                ['Errors', $errors],
            ],
            'errors' => $errors,
        ];
    }

    private function readCsv(string $file): array
    {
        $rows = [];
        $handle = fopen($file, 'r');
        if (! $handle) {
            return $rows;
        }

        $header = fgetcsv($handle);
        if (! $header) {
            fclose($handle);

            return $rows;
        }
        $header = array_map(fn ($h) => strtolower(trim($h)), $header);

        while (($data = fgetcsv($handle)) !== false) {
            if (count($data) !== count($header)) {
                $data = array_pad($data, count($header), null);
            }
            $rows[] = array_combine($header, $data);
        }

        fclose($handle);

        return $rows;
    }
}