<?php

namespace Tests\Feature;

use App\Jobs\ProcessWooCommerceWebhook;
use App\Models\AnalyticsEvent;
use App\Models\InventoryMovement;
use App\Models\NotificationLog;
use App\Models\OrderSnapshot;
use App\Models\WebhookEvent;
use App\Services\WooCommerce\WooCommerceWebhookHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProcessOrderWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_paid_order_creates_snapshot_and_stock_movement_idempotently(): void
    {
        Http::fake([
            'woo.test/*' => Http::response([
                'id' => 21,
                'name' => 'Polo TANISH',
                'type' => 'simple',
                'status' => 'publish',
                'manage_stock' => true,
                'stock_quantity' => 29,
            ]),
        ]);

        $payload = [
            'id' => 60,
            'number' => '60',
            'status' => 'processing',
            'currency' => 'PEN',
            'total' => '99.00',
            'billing' => [
                'first_name' => 'Ana',
                'last_name' => 'Pérez',
                'email' => 'ana@example.test',
            ],
            'payment_method_title' => 'Yape',
            'date_created' => '2026-11-23T10:00:00',
            'line_items' => [[
                'id' => 501,
                'product_id' => 21,
                'name' => 'Polo TANISH',
                'quantity' => 1,
            ]],
        ];

        $event = WebhookEvent::create([
            'delivery_id' => 'delivery-1',
            'payload_hash' => hash('sha256', json_encode($payload)),
            'topic' => 'order.created',
            'payload' => $payload,
            'status' => 'received',
            'received_at' => now(),
        ]);

        $job = new ProcessWooCommerceWebhook($event->id);
        $job->handle(app(WooCommerceWebhookHandler::class));

        $snapshot = OrderSnapshot::where('woo_id', 60)->firstOrFail();
        $this->assertSame('processing', $snapshot->status);
        $this->assertSame('Ana Pérez', $snapshot->customer_name);
        $this->assertNotNull($snapshot->stock_recorded_at);

        $movement = InventoryMovement::firstOrFail();
        $this->assertSame(InventoryMovement::SOURCE_ORDER, $movement->source);
        $this->assertSame('woo-order:60:item:501', $movement->reference);
        $this->assertSame('-1.00', $movement->quantity_change);
        $this->assertSame('30.00', $movement->stock_before);
        $this->assertSame('29.00', $movement->stock_after);

        $this->assertSame('skipped', AnalyticsEvent::firstOrFail()->status);
        $this->assertSame('skipped', NotificationLog::firstOrFail()->status);
        $this->assertSame('processed', $event->fresh()->status);

        // Reprocesar el mismo evento no duplica el movimiento.
        $job->handle(app(WooCommerceWebhookHandler::class));
        $this->assertDatabaseCount('inventory_movements', 1);
    }

    public function test_an_unknown_topic_is_marked_as_processed(): void
    {
        $event = WebhookEvent::create([
            'delivery_id' => 'delivery-2',
            'payload_hash' => hash('sha256', 'unknown'),
            'topic' => 'coupon.created',
            'payload' => ['id' => 1],
            'status' => 'received',
            'received_at' => now(),
        ]);

        (new ProcessWooCommerceWebhook($event->id))->handle(app(WooCommerceWebhookHandler::class));

        $this->assertSame('processed', $event->fresh()->status);
    }
}
