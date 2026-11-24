<?php

namespace Tests\Feature;

use App\Jobs\ProcessWooCommerceWebhook;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WebhookEndpointTest extends TestCase
{
    use RefreshDatabase;

    private const SECRET = 'test-webhook-secret';

    public function test_rejects_requests_without_a_valid_signature(): void
    {
        $response = $this->call(
            'POST',
            '/api/webhooks/woocommerce',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WC_WEBHOOK_SIGNATURE' => 'firma-invalida',
                'HTTP_X_WC_WEBHOOK_TOPIC' => 'order.created',
            ],
            json_encode(['id' => 60])
        );

        $response->assertStatus(401);
        $this->assertDatabaseCount('webhook_events', 0);
    }

    public function test_accepts_a_valid_signature_and_dispatches_the_job(): void
    {
        Queue::fake();

        $raw = json_encode(['id' => 60, 'status' => 'processing', 'line_items' => []]);

        $response = $this->sendSigned($raw, 'order.created', 'delivery-1');

        $response->assertStatus(202);
        $this->assertDatabaseCount('webhook_events', 1);

        $event = WebhookEvent::first();
        $this->assertSame('order.created', $event->topic);
        $this->assertSame('delivery-1', $event->delivery_id);
        $this->assertSame(hash('sha256', $raw), $event->payload_hash);

        Queue::assertPushed(ProcessWooCommerceWebhook::class);
    }

    public function test_duplicate_deliveries_do_not_create_duplicate_events(): void
    {
        Queue::fake();

        $raw = json_encode(['id' => 60, 'status' => 'processing', 'line_items' => []]);

        $this->sendSigned($raw, 'order.created', 'delivery-1')->assertStatus(202);

        // Simula que el job ya procesó el evento.
        WebhookEvent::first()->markProcessed();

        $this->sendSigned($raw, 'order.created', 'delivery-2')->assertStatus(200);

        $this->assertDatabaseCount('webhook_events', 1);
        Queue::assertPushed(ProcessWooCommerceWebhook::class, 1);
    }

    private function sendSigned(string $raw, string $topic, string $deliveryId)
    {
        $signature = base64_encode(hash_hmac('sha256', $raw, self::SECRET, true));

        return $this->call(
            'POST',
            '/api/webhooks/woocommerce',
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_X_WC_WEBHOOK_SIGNATURE' => $signature,
                'HTTP_X_WC_WEBHOOK_TOPIC' => $topic,
                'HTTP_X_WC_WEBHOOK_DELIVERY_ID' => $deliveryId,
            ],
            $raw
        );
    }
}
