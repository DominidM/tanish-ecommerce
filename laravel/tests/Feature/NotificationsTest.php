<?php

namespace Tests\Feature;

use App\Jobs\SendPurchaseToGa4;
use App\Jobs\SendWhatsAppNotification;
use App\Models\AnalyticsEvent;
use App\Models\NotificationLog;
use App\Services\Notifications\WhatsAppNotifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_webhook_driver_sends_and_marks_the_notification_as_sent(): void
    {
        config([
            'analytics.whatsapp.driver' => 'webhook',
            'analytics.whatsapp.url' => 'https://hook.test/send',
            'analytics.whatsapp.token' => 'token-123',
        ]);

        Http::fake(['hook.test/*' => Http::response(['ok' => true])]);

        $log = $this->log();

        (new SendWhatsAppNotification($log->id))->handle(app(WhatsAppNotifier::class));

        $this->assertSame('sent', $log->fresh()->status);
        Http::assertSent(fn ($request): bool => $request->url() === 'https://hook.test/send'
            && $request['to'] === '51999999999');
    }

    public function test_the_meta_driver_sends_a_text_message(): void
    {
        config([
            'analytics.whatsapp.driver' => 'meta',
            'analytics.whatsapp.meta_token' => 'meta-token',
            'analytics.whatsapp.meta_phone_id' => '123456',
            'analytics.whatsapp.template_name' => '',
        ]);

        Http::fake(['graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid']]])]);

        $log = $this->log();

        (new SendWhatsAppNotification($log->id))->handle(app(WhatsAppNotifier::class));

        $this->assertSame('sent', $log->fresh()->status);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), '/123456/messages')
            && $request['type'] === 'text');
    }

    public function test_without_provider_configuration_the_notification_is_skipped(): void
    {
        config(['analytics.whatsapp.driver' => 'log']);

        $log = $this->log();

        (new SendWhatsAppNotification($log->id))->handle(app(WhatsAppNotifier::class));

        $this->assertSame('skipped', $log->fresh()->status);
    }

    public function test_a_purchase_event_is_sent_to_ga4(): void
    {
        config([
            'analytics.ga4.measurement_id' => 'G-TEST',
            'analytics.ga4.api_secret' => 'secret',
        ]);

        Http::fake(['www.google-analytics.com/*' => Http::response('', 204)]);

        $event = AnalyticsEvent::create([
            'name' => 'purchase',
            'event_id' => 'purchase:60',
            'woo_order_id' => 60,
            'payload' => ['transaction_id' => '60', 'value' => 99.0, 'currency' => 'PEN'],
            'status' => 'pending',
        ]);

        (new SendPurchaseToGa4($event->id))->handle();

        $this->assertSame('sent', $event->fresh()->status);
        Http::assertSent(fn ($request): bool => str_contains($request->url(), 'measurement_id=G-TEST'));
    }

    public function test_a_purchase_event_is_skipped_without_ga4_configuration(): void
    {
        config([
            'analytics.ga4.measurement_id' => '',
            'analytics.ga4.api_secret' => '',
        ]);

        $event = AnalyticsEvent::create([
            'name' => 'purchase',
            'event_id' => 'purchase:61',
            'woo_order_id' => 61,
            'payload' => [],
            'status' => 'pending',
        ]);

        (new SendPurchaseToGa4($event->id))->handle();

        $this->assertSame('skipped', $event->fresh()->status);
    }

    private function log(): NotificationLog
    {
        return NotificationLog::create([
            'channel' => 'whatsapp',
            'recipient' => '51999999999',
            'template' => 'order_paid',
            'payload' => [
                'order_id' => 60,
                'order_number' => '60',
                'total' => 99.0,
                'customer' => 'Ana Pérez',
            ],
            'status' => 'pending',
        ]);
    }
}
