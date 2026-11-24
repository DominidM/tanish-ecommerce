<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Services\Notifications\WhatsAppNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendWhatsAppNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 120, 600];

    public function __construct(public readonly int $notificationLogId) {}

    public function handle(WhatsAppNotifier $notifier): void
    {
        $log = NotificationLog::find($this->notificationLogId);

        if (! $log || $log->status === 'sent') {
            return;
        }

        $log->increment('attempts');

        $result = $notifier->send($log);

        $log->update([
            'status' => $result['sent'] ? 'sent' : ($result['skipped'] ? 'skipped' : 'failed'),
            'last_error' => $result['error'],
            'sent_at' => $result['sent'] ? now() : null,
        ]);
    }
}
