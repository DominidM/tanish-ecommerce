<?php

namespace App\Services\Notifications;

use App\Models\NotificationLog;
use Illuminate\Support\Facades\Http;

class WhatsAppNotifier
{
    /**
     * @return array{sent: bool, skipped: bool, error: ?string}
     */
    public function send(NotificationLog $log): array
    {
        return match ((string) config('analytics.whatsapp.driver', 'webhook')) {
            'meta' => $this->sendViaMeta($log),
            'webhook' => $this->sendViaWebhook($log),
            default => ['sent' => false, 'skipped' => true, 'error' => null],
        };
    }

    /**
     * @return array{sent: bool, skipped: bool, error: ?string}
     */
    private function sendViaWebhook(NotificationLog $log): array
    {
        $url = (string) config('analytics.whatsapp.url');

        if ($url === '') {
            return ['sent' => false, 'skipped' => true, 'error' => null];
        }

        $response = Http::timeout(10)
            ->withToken((string) config('analytics.whatsapp.token'))
            ->post($url, [
                'to' => $log->recipient,
                'template' => $log->template,
                'payload' => $log->payload,
            ]);

        return $this->result($response->successful(), $response->body());
    }

    /**
     * WhatsApp Cloud API (Meta).
     *
     * @return array{sent: bool, skipped: bool, error: ?string}
     */
    private function sendViaMeta(NotificationLog $log): array
    {
        $token = (string) config('analytics.whatsapp.meta_token');
        $phoneId = (string) config('analytics.whatsapp.meta_phone_id');

        if ($token === '' || $phoneId === '') {
            return ['sent' => false, 'skipped' => true, 'error' => null];
        }

        $template = (string) config('analytics.whatsapp.template_name');

        $message = $template !== ''
            ? [
                'type' => 'template',
                'template' => [
                    'name' => $template,
                    'language' => ['code' => (string) config('analytics.whatsapp.template_lang', 'es')],
                    'components' => [[
                        'type' => 'body',
                        'parameters' => $this->templateParameters($log),
                    ]],
                ],
            ]
            : [
                'type' => 'text',
                'text' => ['body' => $this->message($log)],
            ];

        $response = Http::timeout(10)
            ->withToken($token)
            ->post("https://graph.facebook.com/v20.0/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'to' => $log->recipient,
                ...$message,
            ]);

        return $this->result($response->successful(), $response->body());
    }

    /**
     * @return array<int, array{type: string, text: string}>
     */
    private function templateParameters(NotificationLog $log): array
    {
        $payload = $log->payload ?? [];

        return [
            ['type' => 'text', 'text' => (string) ($payload['order_number'] ?? $payload['order_id'] ?? '-')],
            ['type' => 'text', 'text' => number_format((float) ($payload['total'] ?? 0), 2)],
            ['type' => 'text', 'text' => (string) ($payload['customer'] ?? '-')],
        ];
    }

    private function message(NotificationLog $log): string
    {
        $payload = $log->payload ?? [];

        return sprintf(
            'Nuevo pedido #%s por %s %s de %s.',
            $payload['order_number'] ?? $payload['order_id'] ?? '-',
            config('tanish.currency', 'PEN'),
            number_format((float) ($payload['total'] ?? 0), 2),
            $payload['customer'] ?? 'cliente'
        );
    }

    /**
     * @return array{sent: bool, skipped: bool, error: ?string}
     */
    private function result(bool $sent, string $body): array
    {
        return [
            'sent' => $sent,
            'skipped' => false,
            'error' => $sent ? null : mb_substr($body, 0, 2000),
        ];
    }
}
