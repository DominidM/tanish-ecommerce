<?php

namespace App\Services\WooCommerce;

use App\Models\WebhookEvent;
use App\Services\WooCommerce\Handlers\HandleCustomerWebhook;
use App\Services\WooCommerce\Handlers\HandleOrderWebhook;
use App\Services\WooCommerce\Handlers\HandleProductWebhook;

class WooCommerceWebhookHandler
{
    public function handle(WebhookEvent $event): void
    {
        match ($event->topic) {
            'order.created', 'order.updated', 'order.deleted' => app(HandleOrderWebhook::class)($event),
            'product.created', 'product.updated', 'product.deleted' => app(HandleProductWebhook::class)($event),
            'customer.created', 'customer.updated', 'customer.deleted' => app(HandleCustomerWebhook::class)($event),
            default => null,
        };
    }
}
