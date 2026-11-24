<?php

namespace App\Jobs;

use App\Models\WebhookEvent;
use App\Services\WooCommerce\WooCommerceWebhookHandler;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessWooCommerceWebhook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 5;

    public int $timeout = 90;

    /** @var array<int, int> */
    public array $backoff = [10, 30, 60, 300, 900];

    public function __construct(public readonly int $webhookEventId) {}

    public function handle(WooCommerceWebhookHandler $handler): void
    {
        $event = WebhookEvent::find($this->webhookEventId);

        if (! $event || $event->status === 'processed') {
            return;
        }

        $event->increment('attempts');
        $event->markProcessing();

        try {
            $handler->handle($event);
            $event->markProcessed();
        } catch (Throwable $exception) {
            $event->markFailed($exception->getMessage());

            throw $exception;
        }
    }

    public function failed(Throwable $exception): void
    {
        WebhookEvent::whereKey($this->webhookEventId)->update([
            'status' => 'failed',
            'last_error' => mb_substr($exception->getMessage(), 0, 2000),
        ]);
    }
}
