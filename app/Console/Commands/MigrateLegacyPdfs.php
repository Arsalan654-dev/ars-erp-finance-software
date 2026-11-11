<?php

namespace App\Console\Commands;

use App\Models\Document;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Migrate legacy PDF documents from a flat directory into the ERP Document vault.
 *
 * Usage: php artisan erp:migrate-pdfs {source} [--client-id=] [--dry-run]
 *
 * The source directory should contain PDFs named like:
 *   CLIENT_123__TaxReturn_2024.pdf
 *   CLIENT_456__BusinessLicense.pdf
 *
 * Patterns:
 *   CLIENT_{id}__{category}_{year}.pdf
 */
class MigrateLegacyPdfs extends Command
{
    protected $signature = 'erp:migrate-pdfs {source : Directory of legacy PDF files} {--client-id= : Restrict to a specific client ID} {--dry-run : Show what would happen}';

    protected $description = 'Import legacy PDFs from a flat directory into the Document vault';

    public function handle(): int
    {
        $source = $this->argument('source');
        $dryRun = $this->option('dry-run');
        $restrictClientId = $this->option('client-id');

        if (! is_dir($source)) {
            $this->error("Source directory not found: {$source}");

            return self::FAILURE;
        }

        $files = glob($source.'/*.pdf');
        $this->info('Found '.count($files).' PDF files in '.$source);

        $imported = 0;
        $skipped = 0;

        foreach ($files as $filepath) {
            $filename = basename($filepath);

            // Parse CLIENT_{id}__{category}_{year}.pdf
            if (! preg_match('/^CLIENT_(\d+)__/', $filename, $m)) {
                $this->warn('Skipping: '.$filename.' (does not match CLIENT_ID__ pattern)');
                $skipped++;
                continue;
            }

            $clientId = (int) $m[1];

            if ($restrictClientId && $clientId !== (int) $restrictClientId) {
                continue;
            }

            $client = \App\Models\Client::find($clientId);
            if (! $client) {
                $this->warn('Skipping: '.$filename.' (client #'.$clientId.' not found)');
                $skipped++;
                continue;
            }

            // Parse category from filename
            $parts = explode('__', $filename);
            $category = $parts[1] ?? 'Legacy';
            $category = pathinfo($category, PATHINFO_FILENAME);
            $category = str_replace(['_', '.'], ' ', $category);
            $category = Str::limit($category, 60, '');

            $description = $client->business_name.' - '.$category;

            if ($dryRun) {
                $this->line('[DRY] '.$filename.' → client #'.$clientId.' ('.$client->business_name.'), category: '.$category);
                $imported++;
                continue;
            }

            // Store into private disk under documents/{client}
            $storedPath = Storage::disk('private')->putFileAs(
                'documents/'.$clientId,
                $filepath,
                Str::slug($category).'_'.time().'.pdf'
            );

            Document::create([
                'client_id' => $clientId,
                'filename' => basename($storedPath),
                'original_filename' => $filename,
                'mime_type' => 'application/pdf',
                'size' => filesize($filepath),
                'storage_path' => $storedPath,
                'storage_disk' => 'private',
                'category' => $category,
                'description' => $description,
                'checksum' => hash_file('sha256', $filepath),
                'uploaded_by' => 1,
            ]);

            $imported++;
        }

        $this->info('Done. '.$imported.' imported, '.$skipped.' skipped.'.($dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }
}