<?php

namespace App\Console\Commands;

use App\Models\ComplianceRecord;
use App\Models\RenewalEvent;
use Illuminate\Console\Command;

/**
 * Daily compliance expiry scan:
 *  - 60 days before expiry -> internal flag
 *  - 30 days before expiry -> client reminder event
 *  - due date -> due alert event
 */
class ComplianceExpiryScan extends Command
{
    protected $signature = 'erp:compliance-scan {--dry-run : Show what would happen}';

    protected $description = 'Scan compliance records for upcoming renewals and fire the 60/30/due events';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $internalDays = (int) config('erp.compliance_reminders.internal_flag_days', 60);
        $clientDays = (int) config('erp.compliance_reminders.client_reminder_days', 30);
        $dueDays = (int) config('erp.compliance_reminders.due_alert_days', 0);

        $records = ComplianceRecord::whereNotNull('expiry_date')->get();

        $count = 0;
        foreach ($records as $record) {
            $days = now()->startOfDay()->diffInDays($record->expiry_date, false);

            if ($days <= $internalDays && $days > $clientDays) {
                if (! $record->renewalEvents()->where('event_type', '60_day_flag')->exists()) {
                    $this->fire($record, '60_day_flag', $dryRun);
                    $count++;
                }
            }

            if ($days <= $clientDays && $days > $dueDays) {
                if (! $record->renewalEvents()->where('event_type', '30_day_reminder')->exists()) {
                    $this->fire($record, '30_day_reminder', $dryRun);
                    $count++;
                }
            }

            if ($days <= $dueDays) {
                if (! $record->renewalEvents()->where('event_type', 'due_alert')->exists()) {
                    $this->fire($record, 'due_alert', $dryRun);
                    $count++;
                    $record->update(['status' => 'expired']);
                }
            }
        }

        $this->info("Compliance scan complete. {$count} events fired.".($dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }

    private function fire(ComplianceRecord $record, string $eventType, bool $dryRun): void
    {
        if ($dryRun) {
            $this->line("[DRY] {$eventType} for #{$record->id} ({$record->document_type})");

            return;
        }

        RenewalEvent::create([
            'compliance_record_id' => $record->id,
            'event_type' => $eventType,
            'triggered_at' => now(),
            'metadata' => ['days_remaining' => now()->startOfDay()->diffInDays($record->expiry_date, false)],
        ]);

        // Send client email for the 30-day reminder and due alert templates
        $templateSlug = match ($eventType) {
            '30_day_reminder' => 'COMPLIANCE_30_DAY',
            'due_alert' => 'COMPLIANCE_DUE',
            default => null,
        };

        if ($templateSlug) {
            $record->loadMissing('client');
            if ($record->client?->email) {
                \App\Services\EventNotifier::complianceReminder([
                    'id' => $record->id,
                    'client' => [
                        'email' => $record->client->email,
                        'business_name' => $record->client->business_name,
                    ],
                    'document_type' => $record->document_type,
                    'reference' => $record->reference_number ?? $record->title,
                    'expiry_date' => $record->expiry_date?->toDateString(),
                ], $templateSlug);
            }
        }
    }
}