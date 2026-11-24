<?php

namespace App\Services\WooCommerce\Handlers;

use App\Models\ProductSnapshot;
use App\Models\WebhookEvent;

class HandleProductWebhook
{
    public function __invoke(WebhookEvent $event): void
    {
        $payload = $event->payload;
        $productId = (int) ($payload['id'] ?? 0);

        if ($productId <= 0) {
            return;
        }

        if ($event->topic === 'product.deleted') {
            ProductSnapshot::where('woo_id', $productId)->update(['status' => 'deleted']);

            return;
        }

        ProductSnapshot::updateOrCreate(
            ['woo_id' => $productId],
            ProductSnapshot::fromWoo($payload)
        );
    }
}
