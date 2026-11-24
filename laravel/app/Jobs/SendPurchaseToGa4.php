<?php

namespace App\Jobs;

use App\Models\AnalyticsEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendPurchaseToGa4 implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public readonly int $analyticsEventId) {}

    public function handle(): void
    {
        $event = AnalyticsEvent::find($this->analyticsEventId);

        if (! $event || $event->status === 'sent') {
            return;
        }

        $measurementId = (string) config('analytics.ga4.measurement_id');
        $apiSecret = (string) config('analytics.ga4.api_secret');

        if ($measurementId === '' || $apiSecret === '') {
            $event->update(['status' => 'skipped']);

            return;
        }

        $payload = $event->payload ?? [];

        $response = Http::timeout(10)->post(
            config('analytics.ga4.endpoint').'?'.http_build_query([
                'measurement_id' => $measurementId,
                'api_secret' => $apiSecret,
            ]),
            [
                'client_id' => 'woo-'.($event->woo_order_id ?? 'unknown'),
                'events' => [[
                    'name' => $event->name,
                    'params' => $payload,
                ]],
            ]
        );

        $event->update([
            'status' => $response->successful() ? 'sent' : 'failed',
            'response' => ['status' => $response->status()],
            'sent_at' => $response->successful() ? now() : null,
        ]);
    }
}
