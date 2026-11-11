<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\User;

/**
 * Broadcasts in-app notifications to administrators so that every
 * sensitive action (payments, invoices, leads, expenses...) is visible
 * to the admin without having to hunt through audit logs.
 */
class AdminNotifier
{
    /**
     * Notify every active user holding one of the given roles.
     *
     * @param  string|null  $link  route path (e.g. "/erp/payments/3") the notification points to
     * @param  int|null  $exceptUserId  skip the acting user (no self-notification)
     */
    public static function notify(
        string $type,
        string $title,
        string $body,
        ?string $link = null,
        array $roles = ['super_admin'],
        ?int $exceptUserId = null
    ): int {
        $users = User::where('is_active', true)
            ->whereHas('roles', function ($q) use ($roles): void {
                $q->whereIn('name', $roles);
            })
            ->when($exceptUserId, function ($query) use ($exceptUserId): void {
                $query->where('id', '!=', $exceptUserId);
            })
            ->get();

        $count = 0;
        foreach ($users as $user) {
            Notification::create([
                'user_id' => $user->id,
                'type' => $type,
                'title' => $title,
                'body' => $body,
                'link' => $link,
                'is_read' => false,
            ]);
            $count++;
        }

        return $count;
    }

    /**
     * Flag a payment whose entered amount does not match the screenshot.
     * Goes straight to the admin (and finance) — the fraud/mismatch alert.
     */
    public static function paymentMismatch(
        \App\Models\Payment $payment,
        float $enteredAmount,
        float $screenshotAmount,
        ?int $recordedBy = null
    ): void {
        self::notify(
            'payment_mismatch',
            'Payment amount mismatch — '.$payment->payment_number,
            sprintf(
                'Entered %s %s but the screenshot shows %s %s. Recorded by %s. Immediate review required.',
                $payment->currency,
                number_format($enteredAmount, 2),
                $payment->currency,
                number_format($screenshotAmount, 2),
                \App\Models\User::find($recordedBy)?->name ?? 'unknown user'
            ),
            '/erp/finance/payments/'.$payment->id,
            ['super_admin', 'finance'],
            $recordedBy
        );
    }
}
