<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WebhookEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'delivery_id',
        'payload_hash',
        'topic',
        'resource',
        'event_action',
        'source',
        'payload',
        'status',
        'attempts',
        'last_error',
        'source_updated_at',
        'received_at',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'source_updated_at' => 'datetime',
            'received_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    public function markProcessing(): void
    {
        $this->forceFill(['status' => 'processing'])->save();
    }

    public function markProcessed(): void
    {
        $this->forceFill([
            'status' => 'processed',
            'processed_at' => now(),
            'last_error' => null,
        ])->save();
    }

    public function markFailed(string $error): void
    {
        $this->forceFill([
            'status' => 'failed',
            'last_error' => mb_substr($error, 0, 2000),
        ])->save();
    }
}
