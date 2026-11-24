<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public readonly int $notificationLogId) {}

    public function handle(): void
    {
        $log = NotificationLog::find($this->notificationLogId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        $url = (string) config('analytics.whatsapp.url');
        $token = (string) config('analytics.whatsapp.token');

        if ($url === '') {
            $log->update(['status' => 'skipped']);

            return;
        }

        $log->increment('attempts');

        $response = Http::timeout(10)
            ->withToken($token)
            ->post($url, [
                'to' => $log->recipient,
                'template' => $log->template,
                'payload' => $log->payload,
            ]);

        $log->update([
            'status' => $response->successful() ? 'sent' : 'failed',
            'last_error' => $response->successful() ? null : mb_substr($response->body(), 0, 2000),
            'sent_at' => $response->successful() ? now() : null,
        ]);
    }
}
