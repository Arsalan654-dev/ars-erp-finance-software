<?php

namespace App\Services;

use App\Models\Client;
use App\Models\Lead;
use App\Models\WhatsappAccount;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    public static function enabled(): bool
    {
        return WhatsappAccount::active()->exists();
    }

    /**
     * Send via a specific account (or first active account).
     */
    public static function send(string $phone, string $message, ?WhatsappAccount $account = null): bool
    {
        $account = $account ?? WhatsappAccount::active()->first();
        if (! $account) {
            return false;
        }

        try {
            $apiUrl = $account->api_url ?: 'https://graph.facebook.com/v19.0';
            $token = $account->api_token_decrypted;

            if ($account->provider === 'evolution') {
                return self::sendEvolution($account, $phone, $message);
            }

            // Meta Cloud API
            $response = Http::withToken($token)
                ->post(rtrim($apiUrl, '/').'/'.$account->phone_number_id.'/messages', [
                    'messaging_product' => 'whatsapp',
                    'to' => self::normalize($phone),
                    'type' => 'text',
                    'text' => ['preview_url' => false, 'body' => $message],
                ]);

            if (! $response->successful()) {
                Log::warning('WhatsApp send failed', ['status' => $response->status(), 'body' => $response->json(), 'account' => $account->name]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::error('WhatsApp send error: '.$e->getMessage(), ['account' => $account->name]);

            return false;
        }
    }

    /**
     * Send via Evolution API.
     */
    private static function sendEvolution(WhatsappAccount $account, string $phone, string $message): bool
    {
        $apiUrl = rtrim($account->api_url, '/');
        $token = $account->api_token_decrypted;

        $response = Http::withToken($token)
            ->post($apiUrl.'/message/sendText/'.$account->phone_number_id, [
                'number' => self::normalize($phone),
                'text' => $message,
            ]);

        if (! $response->successful()) {
            Log::warning('Evolution send failed', ['status' => $response->status(), 'body' => $response->json()]);

            return false;
        }

        return true;
    }

    /**
     * Handle inbound webhook from any configured account.
     */
    public static function handleInbound(array $payload, ?WhatsappAccount $account = null): void
    {
        $changes = data_get($payload, 'entry.0.changes', []);

        // Auto-detect account from payload if not provided
        if (! $account) {
            $phoneNumberId = data_get($payload, 'entry.0.changes.0.value.metadata.phone_number_id')
                ?? data_get($payload, 'instance');
            if ($phoneNumberId) {
                $account = WhatsappAccount::where('phone_number_id', $phoneNumberId)->first();
            }
        }

        foreach ($changes as $change) {
            $messages = data_get($change, 'value.messages', []);
            foreach ($messages as $message) {
                $from = $message['from'] ?? null;
                $text = data_get($message, 'text.body');
                $type = $message['type'] ?? null;

                Log::info('WhatsApp inbound message', [
                    'from' => $from,
                    'type' => $type,
                    'text' => $text,
                    'account' => $account?->name,
                ]);

                if ($from) {
                    $existingClient = Client::where('phone', 'like', "%{$from}%")->first();
                    if (! $existingClient) {
                        $existingLead = Lead::where('phone', 'like', "%{$from}%")
                            ->whereIn('status', ['new', 'contacted', 'qualified'])
                            ->first();

                        if (! $existingLead) {
                            $client = Client::create([
                                'phone' => $from,
                                'business_name' => 'WhatsApp Lead',
                                'status' => 'lead',
                            ]);

                            Lead::create([
                                'client_id' => $client->id,
                                'source' => 'whatsapp',
                                'phone' => $from,
                                'contact_name' => null,
                                'business_name' => null,
                                'notes' => $text ? "First message: {$text}" : null,
                                'status' => 'new',
                                'priority' => 'medium',
                                'department' => 'GENERAL',
                            ]);
                        } elseif ($text) {
                            $existingLead->activities()->create([
                                'user_id' => null,
                                'type' => 'whatsapp',
                                'subject' => 'Inbound message',
                                'body' => $text,
                                'metadata' => ['from' => $from, 'account' => $account?->name],
                            ]);
                        }
                    }
                }

                // Auto-reply if configured
                if ($from && $text && $account) {
                    self::send($from, 'Thank you for your message. ARS ERP has received it and will respond shortly.', $account);
                }
            }
        }
    }

    private static function normalize(string $phone): string
    {
        $phone = preg_replace('/[^0-9+]/', '', $phone);
        if (str_starts_with($phone, '0')) {
            $phone = '+44'.substr($phone, 1);
        }
        if (! str_starts_with($phone, '+')) {
            $phone = '+'.$phone;
        }

        return $phone;
    }
}
