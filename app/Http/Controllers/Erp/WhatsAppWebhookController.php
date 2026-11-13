<?php

namespace App\Http\Controllers\Erp;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Notification;
use App\Models\WhatsappAccount;
use App\Services\AuditService;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    /**
     * Webhook handler — supports per-account phone_number_id in URL.
     * POST/GET /erp/whatsapp/webhook/{phone_number_id?}
     */
    public function webhook(Request $request, ?string $phone_number_id = null)
    {
        // Find the account if phone_number_id is provided in URL
        $account = null;
        if ($phone_number_id) {
            $account = WhatsappAccount::where('phone_number_id', $phone_number_id)->first();
        }

        // Meta webhook verification (GET challenge)
        if ($request->isMethod('GET')) {
            $mode = $request->get('hub_mode');
            $token = $request->get('hub_verify_token');
            $challenge = $request->get('hub_challenge');

            // Check verify token against account or fallback to config
            $expectedToken = $account?->verify_token
                ? decrypt($account->verify_token)
                : config('erp.whatsapp.verify_token');

            if ($mode === 'subscribe' && $token === $expectedToken) {
                return response($challenge);
            }

            return response('Verification failed', 403);
        }

        // Inbound message (POST)
        try {
            $payload = $request->all();
            WhatsAppService::handleInbound($payload, $account);

            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook error: '.$e->getMessage());

            return response()->json(['status' => 'error', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Send a WhatsApp message to a client.
     */
    public function sendMessage(Request $request, Client $client)
    {
        $data = $request->validate([
            'message' => ['required', 'string', 'max:1000'],
        ]);

        if (! $client->phone) {
            return back()->with('error', 'Client has no phone number on file.');
        }

        $sent = WhatsAppService::send($client->phone, $data['message']);

        if (! $sent) {
            return back()->with('error', 'WhatsApp is not configured or no active account found.');
        }

        Notification::create([
            'user_id' => auth()->id(),
            'type' => 'whatsapp_sent',
            'title' => 'WhatsApp message sent to '.$client->business_name,
            'body' => $data['message'],
            'link' => route('erp.clients.show', $client),
        ]);

        AuditService::log('WHATSAPP_SEND', 'client', $client->id, ['to' => $client->phone]);

        return back()->with('success', 'WhatsApp message sent.');
    }

    /**
     * Send template message.
     */
    public function sendTemplate(Request $request, Client $client)
    {
        $data = $request->validate([
            'template' => ['required', 'string', 'in:quotation,invoice,payment,renewal'],
            'reference' => ['nullable', 'string'],
        ]);

        if (! $client->phone) {
            return back()->with('error', 'Client has no phone number on file.');
        }

        $message = match ($data['template']) {
            'quotation' => "Hello {$client->business_name}, your quotation {$data['reference']} is ready from ARS ERP. Please check your email or contact us for details.",
            'invoice' => "Hello {$client->business_name}, invoice {$data['reference']} has been issued by ARS ERP. Thank you for your business.",
            'payment' => "Hello {$client->business_name}, we received your payment. Thank you!",
            'renewal' => "Hello {$client->business_name}, a reminder that your document is due for renewal. Please contact ARS ERP.",
            default => "Hello {$client->business_name}, a message from ARS ERP.",
        };

        $sent = WhatsAppService::send($client->phone, $message);

        return back()->with($sent ? 'success' : 'error', $sent
            ? 'WhatsApp template message sent.'
            : 'WhatsApp is not configured.');
    }
}
