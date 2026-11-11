<?php

namespace App\Services;

use App\Models\Client;
use App\Models\EmailQueue;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Task;

/**
 * Business-event driven email dispatcher.
 *
 * Each business action (quotation sent, invoice sent, payment received,
 * task assigned, compliance reminder) calls a static `on*` method here,
 * which queues a template-rendered email (optionally with a PDF attachment)
 * through EmailService::sendFromTemplate().
 */
class EventNotifier
{
    /**
     * Admin recipients (active super admins) — always copied on
     * client-facing quotation / invoice / payment emails.
     *
     * @return array<int, array{email: string, name: string}>
     */
    public static function adminRecipients(): array
    {
        return \App\Models\User::where('is_active', true)
            ->whereHas('roles', fn ($q) => $q->where('name', 'super_admin'))
            ->get()
            ->map(fn ($u) => ['email' => $u->email, 'name' => $u->name])
            ->filter(fn ($r) => ! empty($r['email']))
            ->values()
            ->all();
    }

    /**
     * Queue the same event email to the client AND every admin.
     */
    private static function queueForClientAndAdmins(
        string $eventType,
        ?string $clientEmail,
        ?string $clientName,
        array $variables,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?array $attachment = null
    ): void {
        self::queue($eventType, $clientEmail, $clientName, $variables, $relatedId, $relatedType, $attachment);

        foreach (self::adminRecipients() as $admin) {
            if ($admin['email'] === $clientEmail) {
                continue;
            }
            self::queue($eventType, $admin['email'], $admin['name'], $variables, $relatedId, $relatedType, $attachment);
        }
    }

    private static function queue(
        string $eventType,
        ?string $toEmail,
        ?string $toName,
        array $variables,
        ?int $relatedId = null,
        ?string $relatedType = null,
        ?array $attachment = null
    ): ?EmailQueue {
        if (! $toEmail) {
            return null;
        }

        $queueItem = EmailService::sendFromTemplate(
            $eventType,
            $toEmail,
            $toName,
            $variables,
            $relatedId,
            $relatedType
        );

        if ($attachment && file_exists($attachment['path'])) {
            $existing = $queueItem->attachments ?: [];
            $queueItem->update([
                'attachments' => array_values(array_filter(array_merge((array) $existing, [$attachment]), function ($a) {
                    return is_array($a) && isset($a['path']);
                })),
            ]);
        }

        return $queueItem;
    }

    public static function quotationSent(Quotation $quotation): void
    {
        $quotation->loadMissing(['client', 'creator']);
        $client = $quotation->client;

        $pdfPath = $client
            ? self::writePdf(PdfService::quotation($quotation)->output(), "quotation-{$quotation->quotation_number}.pdf")
            : null;

        self::queueForClientAndAdmins(
            'QUOTATION_SENT',
            $client?->email,
            $client?->business_name,
            [
                'client_name' => $client->business_name ?? 'Client',
                'quotation_number' => $quotation->quotation_number,
                'total' => number_format($quotation->total, 2),
                'currency' => $quotation->currency,
                'valid_until' => $quotation->valid_until?->format('d M Y') ?? 'N/A',
            ],
            $quotation->id,
            'quotation',
            $pdfPath
        );
    }

    /**
     * Generic quotation status-change email → client + admin
     * (accepted, converted, rejected, expired...).
     */
    public static function quotationStatusChanged(Quotation $quotation, string $status): void
    {
        if (! \App\Models\EmailTemplate::where('slug', 'QUOTATION_STATUS_CHANGED')->where('is_active', true)->exists()) {
            return;
        }

        $quotation->loadMissing('client');
        $client = $quotation->client;

        self::queueForClientAndAdmins(
            'QUOTATION_STATUS_CHANGED',
            $client?->email,
            $client?->business_name,
            [
                'client_name' => $client->business_name ?? 'Client',
                'quotation_number' => $quotation->quotation_number,
                'status' => ucfirst($status),
                'total' => number_format($quotation->total, 2),
                'currency' => $quotation->currency,
            ],
            $quotation->id,
            'quotation'
        );
    }

    /**
     * Generic invoice status-change email → client + admin
     * (locked, cancelled, paid, partially_paid...). "Sent" uses invoiceSent().
     */
    public static function invoiceStatusChanged(Invoice $invoice, string $status): void
    {
        if (! \App\Models\EmailTemplate::where('slug', 'INVOICE_STATUS_CHANGED')->where('is_active', true)->exists()) {
            return;
        }

        $invoice->loadMissing('client');
        $client = $invoice->client;

        self::queueForClientAndAdmins(
            'INVOICE_STATUS_CHANGED',
            $client?->email,
            $client?->business_name,
            [
                'client_name' => $client->business_name ?? 'Client',
                'invoice_number' => $invoice->invoice_number,
                'status' => ucfirst(str_replace('_', ' ', $status)),
                'total' => number_format($invoice->total, 2),
                'currency' => $invoice->currency,
                'balance' => number_format($invoice->balance ?? 0, 2),
                'due_date' => $invoice->due_date?->format('d M Y') ?? 'N/A',
            ],
            $invoice->id,
            'invoice'
        );
    }

    /**
     * Auto template when a lead is marked lost (reason + special note) → admins.
     */
    public static function leadLost(\App\Models\Lead $lead): void
    {
        if (! \App\Models\EmailTemplate::where('slug', 'LEAD_LOST')->where('is_active', true)->exists()) {
            return;
        }

        foreach (self::adminRecipients() as $admin) {
            self::queue(
                'LEAD_LOST',
                $admin['email'],
                $admin['name'],
                [
                    'lead_name' => $lead->business_name ?? $lead->contact_name ?? 'N/A',
                    'lost_reason' => $lead->lost_reason ?? 'Not specified',
                    'lost_note' => $lead->lost_note ?? '—',
                    'assigned_to' => $lead->assignee?->name ?? 'Unassigned',
                    'department' => $lead->department ?? '—',
                ],
                $lead->id,
                'lead'
            );
        }
    }

    public static function invoiceSent(Invoice $invoice): void
    {
        $invoice->loadMissing(['client', 'items']);
        $client = $invoice->client;

        $pdfPath = $client
            ? self::writePdf(PdfService::invoice($invoice)->output(), "invoice-{$invoice->invoice_number}.pdf")
            : null;

        self::queueForClientAndAdmins(
            'INVOICE_SENT',
            $client?->email,
            $client?->business_name,
            [
                'client_name' => $client->business_name ?? 'Client',
                'invoice_number' => $invoice->invoice_number,
                'total' => number_format($invoice->total, 2),
                'currency' => $invoice->currency,
                'due_date' => $invoice->due_date?->format('d M Y') ?? 'N/A',
            ],
            $invoice->id,
            'invoice',
            $pdfPath
        );
    }

    public static function paymentReceived(Payment $payment): void
    {
        $payment->loadMissing('client');
        $client = $payment->client;

        $pdfPath = $client
            ? self::writePdf(PdfService::receipt($payment)->output(), "receipt-{$payment->payment_number}.pdf")
            : null;

        self::queueForClientAndAdmins(
            'PAYMENT_RECEIVED',
            $client?->email,
            $client?->business_name,
            [
                'client_name' => $client->business_name ?? 'Client',
                'amount' => number_format($payment->amount, 2),
                'currency' => $payment->currency,
                'reference' => $payment->reference_number ?? $payment->payment_number,
            ],
            $payment->id,
            'payment',
            $pdfPath
        );
    }

    public static function taskAssigned(Task $task, int $assigneeId): void
    {
        $assignee = \App\Models\User::find($assigneeId);
        if (! $assignee || ! $assignee->email) {
            return;
        }

        self::queue(
            'TASK_ASSIGNED',
            $assignee->email,
            $assignee->name,
            [
                'assignee_name' => $assignee->name,
                'task_number' => $task->task_number,
                'task_title' => $task->title,
                'due_date' => $task->due_date?->format('d M Y') ?? 'N/A',
                'priority' => ucfirst($task->priority),
            ],
            $task->id,
            'task'
        );
    }

    public static function complianceReminder(array $record, string $eventType, array $extraVariables = []): void
    {
        $client = $record['client'] ?? null;
        if (! $client || empty($client['email'])) {
            return;
        }

        $variables = array_merge([
            'client_name' => $client['business_name'] ?? '',
            'document_type' => $record['document_type'] ?? '',
            'reference' => $record['reference'] ?? '',
            'expiry_date' => isset($record['expiry_date']) && $record['expiry_date'] ? \Illuminate\Support\Carbon::parse($record['expiry_date'])->format('d M Y') : 'N/A',
        ], $extraVariables);

        self::queue(
            $eventType,
            $client['email'],
            $client['business_name'] ?? null,
            $variables,
            $record['id'] ?? null,
            'compliance'
        );
    }

    private static function writePdf(string $pdfContent, string $filename): array
    {
        $dir = storage_path('app/email-attachments');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        $path = $dir.'/'.uniqid('att_').'_'.$filename;
        file_put_contents($path, $pdfContent);

        return ['path' => $path, 'name' => $filename];
    }
}
