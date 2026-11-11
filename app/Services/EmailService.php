<?php

namespace App\Services;

use App\Models\EmailLog;
use App\Models\EmailQueue;
use App\Models\EmailTemplate;
use Illuminate\Support\Facades\Mail;

/**
 * Automated email engine: template-based, queued dispatch, full logging.
 */
class EmailService
{
    /**
     * Queue an email for a given event type, rendered from a template.
     */
    public static function sendFromTemplate(
        string $eventType,
        string $toEmail,
        ?string $toName,
        array $variables,
        ?int $relatedId = null,
        ?string $relatedType = null
    ): EmailQueue {
        $template = EmailTemplate::where('slug', $eventType)->where('is_active', true)->first();

        $subject = $template ? self::render($template->subject, $variables) : $eventType;
        $body = $template ? self::render($template->body, $variables) : '';

        // Dedup: if an identical queued/sent email already exists in the last hour, skip
        $recent = EmailQueue::where('to_email', $toEmail)
            ->where('subject', $subject)
            ->whereIn('status', ['queued', 'processing', 'sent'])
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if ($recent) {
            return EmailQueue::where('to_email', $toEmail)
                ->where('subject', $subject)
                ->where('status', 'queued')
                ->latest()
                ->first();
        }

        $queued = EmailQueue::create([
            'to_email' => $toEmail,
            'to_name' => $toName,
            'subject' => $subject,
            'body' => $body,
            'event_type' => $eventType,
            'status' => 'queued',
            'scheduled_at' => now(),
        ]);

        EmailLog::create([
            'recipient_email' => $toEmail,
            'recipient_name' => $toName,
            'subject' => $subject,
            'template_slug' => $eventType,
            'event_type' => $eventType,
            'related_entity_id' => $relatedId,
            'related_entity_type' => $relatedType,
            'status' => 'pending',
        ]);

        return $queued;
    }

    /**
     * Process one queued email (called by worker / command).
     * Uses two-phase approach: claim item atomically, then send outside lock.
     */
    public static function process(EmailQueue $queueItem): void
    {
        // Phase 1: Claim the item atomically (short lock)
        $item = \Illuminate\Support\Facades\DB::transaction(function () use ($queueItem) {
            $item = EmailQueue::lockForUpdate()->find($queueItem->id);

            if (! $item || $item->status === 'sent' || $item->status === 'failed') {
                return null;
            }

            $item->update(['status' => 'processing']);

            return $item;
        });

        // If item was already claimed or not found, exit
        if (! $item) {
            return;
        }

        // Phase 2: Send email (no lock held — SMTP can take its time)
        try {
            Mail::html($item->body, function ($message) use ($item): void {
                $message->to($item->to_email, $item->to_name)
                    ->subject($item->subject)
                    ->from(config('mail.from.address'), config('mail.from.name'));

                foreach ($item->attachments ?? [] as $attachment) {
                    if (file_exists($attachment['path'])) {
                        $message->attach($attachment['path'], ['as' => $attachment['name'] ?? basename($attachment['path'])]);
                    }
                }
            });

            // Phase 3: Mark as sent (short transaction)
            \Illuminate\Support\Facades\DB::transaction(function () use ($item) {
                $item->update([
                    'status' => 'sent',
                    'processed_at' => now(),
                ]);

                EmailLog::where('recipient_email', $item->to_email)
                    ->where('template_slug', $item->event_type)
                    ->where('status', 'pending')
                    ->latest()
                    ->first()
                    ?->update(['status' => 'sent', 'sent_at' => now()]);
            });
        } catch (\Throwable $e) {
            // Phase 3 (failure): Update retry status
            \Illuminate\Support\Facades\DB::transaction(function () use ($item, $e) {
                $retry = $item->retry_count + 1;
                $item->update([
                    'status' => $retry >= $item->max_retries ? 'failed' : 'queued',
                    'retry_count' => $retry,
                    'error' => substr($e->getMessage(), 0, 500),
                ]);
            });
        }
    }

    private static function render(string $text, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $text = str_replace('{{'.$key.'}}', (string) $value, $text);
        }

        return $text;
    }
}