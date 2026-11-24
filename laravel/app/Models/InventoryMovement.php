<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryMovement extends Model
{
    use HasFactory;

    public const TYPE_ENTRY = 'entry';

    public const TYPE_EXIT = 'exit';

    public const TYPE_ADJUSTMENT = 'adjustment';

    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_ORDER = 'order';

    public const SOURCE_REVERSAL = 'reversal';

    public const SOURCE_RECONCILIATION = 'reconciliation';

    protected $fillable = [
        'product_snapshot_id',
        'woo_product_id',
        'movement_type',
        'quantity_change',
        'stock_before',
        'stock_after',
        'reason',
        'user_id',
        'source',
        'reference',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'quantity_change' => 'decimal:2',
            'stock_before' => 'decimal:2',
            'stock_after' => 'decimal:2',
            'metadata' => 'array',
        ];
    }

    public function productSnapshot(): BelongsTo
    {
        return $this->belongsTo(ProductSnapshot::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('movement_type', $type);
    }

    public function scopeManual($query)
    {
        return $query->where('source', self::SOURCE_MANUAL);
    }
}
