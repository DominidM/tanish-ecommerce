<?php

namespace App\Services\WooCommerce\Handlers;

use App\Jobs\SendPurchaseToGa4;
use App\Jobs\SendWhatsAppNotification;
use App\Models\AnalyticsEvent;
use App\Models\InventoryMovement;
use App\Models\NotificationLog;
use App\Models\OrderSnapshot;
use App\Models\WebhookEvent;
use App\Services\Inventory\InventoryService;

class HandleOrderWebhook
{
    public function __construct(private readonly InventoryService $inventory) {}

    public function __invoke(WebhookEvent $event): void
    {
        $payload = $event->payload;
        $orderId = (int) ($payload['id'] ?? 0);

        if ($orderId <= 0) {
            return;
        }

        if ($event->topic === 'order.deleted') {
            OrderSnapshot::where('woo_id', $orderId)->update(['status' => 'deleted']);

            return;
        }

        $snapshot = OrderSnapshot::updateOrCreate(
            ['woo_id' => $orderId],
            OrderSnapshot::fromWoo($payload)
        );

        if ($snapshot->isPaid() && ! $snapshot->stock_recorded_at) {
            $this->recordStock($snapshot);
            $snapshot->forceFill(['stock_recorded_at' => now()])->save();

            $this->recordAnalytics($snapshot);
            $this->notifyAdmin($snapshot);
        }

        if ($snapshot->status === 'cancelled' && $snapshot->stock_recorded_at) {
            $this->reverseStock($snapshot);
            $snapshot->forceFill(['stock_recorded_at' => null])->save();
        }
    }

    private function recordStock(OrderSnapshot $snapshot): void
    {
        foreach ($snapshot->line_items ?? [] as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $this->inventory->recordSale(
                (int) $snapshot->woo_id,
                $productId,
                $quantity,
                isset($item['id']) ? (int) $item['id'] : null
            );
        }
    }

    private function reverseStock(OrderSnapshot $snapshot): void
    {
        foreach ($snapshot->line_items ?? [] as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (float) ($item['quantity'] ?? 0);

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $movement = $this->inventory->recordRestoration(
                (int) $snapshot->woo_id,
                $productId,
                $quantity,
                isset($item['id']) ? (int) $item['id'] : null
            );

            if (! $movement instanceof InventoryMovement) {
                // No se pudo atribuir la reposición: queda para conciliación manual.
                continue;
            }
        }
    }

    private function recordAnalytics(OrderSnapshot $snapshot): void
    {
        $event = AnalyticsEvent::firstOrCreate(
            ['event_id' => "purchase:{$snapshot->woo_id}"],
            [
                'name' => 'purchase',
                'woo_order_id' => $snapshot->woo_id,
                'payload' => [
                    'transaction_id' => (string) ($snapshot->number ?? $snapshot->woo_id),
                    'value' => (float) $snapshot->total,
                    'currency' => $snapshot->currency ?? 'PEN',
                    'items' => $snapshot->line_items ?? [],
                ],
                'status' => 'pending',
            ]
        );

        if ($event->status !== 'sent') {
            SendPurchaseToGa4::dispatch($event->id);
        }
    }

    private function notifyAdmin(OrderSnapshot $snapshot): void
    {
        $log = NotificationLog::create([
            'channel' => 'whatsapp',
            'recipient' => (string) config('analytics.whatsapp.admin_number', 'admin'),
            'template' => 'order_paid',
            'payload' => [
                'order_id' => $snapshot->woo_id,
                'order_number' => $snapshot->number,
                'total' => (float) $snapshot->total,
                'customer' => $snapshot->customer_name,
            ],
            'status' => 'pending',
        ]);

        SendWhatsAppNotification::dispatch($log->id);
    }
}
