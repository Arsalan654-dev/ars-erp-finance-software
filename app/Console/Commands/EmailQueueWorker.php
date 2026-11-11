<?php

namespace App\Console\Commands;

use App\Models\EmailQueue;
use App\Services\EmailService;
use Illuminate\Console\Command;

/**
 * Email queue worker — processes queued emails (called frequently).
 */
class EmailQueueWorker extends Command
{
    protected $signature = 'erp:email-process {--limit=20 : Max emails to process}';

    protected $description = 'Process pending emails from the queue';

    public function handle(): int
    {
        $limit = (int) $this->option('limit');

        $pending = EmailQueue::where('status', 'queued')
            ->where(function ($q) {
                $q->whereNull('scheduled_at')->orWhere('scheduled_at', '<=', now());
            })
            ->limit($limit)
            ->get();

        $processed = 0;
        $failed = 0;

        foreach ($pending as $item) {
            EmailService::process($item);
            if ($item->fresh()->status === 'sent') {
                $processed++;
            } else {
                $failed++;
            }
        }

        $this->info("Email worker: {$processed} sent, {$failed} failed/retried.");

        return self::SUCCESS;
    }
}