<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ProcessWooCommerceWebhook;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WooCommerceWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $secret = (string) config('woocommerce.webhook_secret');

        if ($secret === '') {
            return response()->json(['error' => 'webhook_secret_not_configured'], 503);
        }

        $raw = $request->getContent();
        $signature = (string) $request->header('X-WC-Webhook-Signature', '');
        $expected = base64_encode(hash_hmac('sha256', $raw, $secret, true));

        if (! hash_equals($expected, $signature)) {
            return response()->json(['error' => 'invalid_signature'], 401);
        }

        $payload = json_decode($raw, true);

        if (! is_array($payload)) {
            return response()->json(['error' => 'invalid_payload'], 400);
        }

        $event = WebhookEvent::firstOrCreate(
            ['payload_hash' => hash('sha256', $raw)],
            [
                'delivery_id' => $request->header('X-WC-Webhook-Delivery-ID'),
                'topic' => (string) $request->header('X-WC-Webhook-Topic'),
                'resource' => $request->header('X-WC-Webhook-Resource'),
                'event_action' => $request->header('X-WC-Webhook-Event'),
                'source' => $request->header('X-WC-Webhook-Source'),
                'payload' => $payload,
                'status' => 'received',
                'received_at' => now(),
            ]
        );

        if ($event->status === 'processed') {
            return response()->json([
                'ok' => true,
                'duplicate' => true,
                'event_id' => $event->id,
            ]);
        }

        ProcessWooCommerceWebhook::dispatch($event->id);

        return response()->json([
            'ok' => true,
            'event_id' => $event->id,
            'status' => $event->status,
        ], 202);
    }
}
