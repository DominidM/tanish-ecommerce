<?php

namespace App\Services\WooCommerce\Handlers;

use App\Models\CustomerSnapshot;
use App\Models\WebhookEvent;

class HandleCustomerWebhook
{
    public function __invoke(WebhookEvent $event): void
    {
        $payload = $event->payload;
        $customerId = (int) ($payload['id'] ?? 0);

        if ($customerId <= 0) {
            return;
        }

        if ($event->topic === 'customer.deleted') {
            CustomerSnapshot::where('woo_id', $customerId)->delete();

            return;
        }

        CustomerSnapshot::updateOrCreate(
            ['woo_id' => $customerId],
            CustomerSnapshot::fromWoo($payload)
        );
    }
}
