<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'woo_id',
        'number',
        'status',
        'currency',
        'total',
        'customer_id',
        'customer_name',
        'customer_email',
        'payment_method',
        'payment_method_title',
        'date_created',
        'date_paid',
        'line_items',
        'payload',
        'stock_recorded_at',
    ];

    protected function casts(): array
    {
        return [
            'total' => 'decimal:2',
            'date_created' => 'datetime',
            'date_paid' => 'datetime',
            'line_items' => 'array',
            'payload' => 'array',
            'stock_recorded_at' => 'datetime',
        ];
    }

    /**
     * @param  array<string, mixed>  $order
     * @return array<string, mixed>
     */
    public static function fromWoo(array $order): array
    {
        $billing = $order['billing'] ?? [];

        return [
            'number' => isset($order['number']) ? (string) $order['number'] : null,
            'status' => $order['status'] ?? null,
            'currency' => $order['currency'] ?? null,
            'total' => $order['total'] ?? 0,
            'customer_id' => $order['customer_id'] ?? null,
            'customer_name' => trim(($billing['first_name'] ?? '').' '.($billing['last_name'] ?? '')) ?: null,
            'customer_email' => $billing['email'] ?? $order['billing']['email'] ?? null,
            'payment_method' => $order['payment_method'] ?? null,
            'payment_method_title' => $order['payment_method_title'] ?? null,
            'date_created' => $order['date_created'] ?? null,
            'date_paid' => $order['date_paid'] ?? null,
            'line_items' => self::mapLineItems($order['line_items'] ?? []),
            'payload' => $order,
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private static function mapLineItems(array $items): array
    {
        return array_map(static fn (array $item): array => [
            'id' => isset($item['id']) ? (int) $item['id'] : null,
            'product_id' => isset($item['product_id']) ? (int) $item['product_id'] : null,
            'variation_id' => isset($item['variation_id']) ? (int) $item['variation_id'] : null,
            'name' => $item['name'] ?? null,
            'quantity' => isset($item['quantity']) ? (float) $item['quantity'] : 0.0,
            'sku' => $item['sku'] ?? null,
            'total' => $item['total'] ?? null,
        ], $items);
    }

    public function isPaid(): bool
    {
        return in_array($this->status, ['processing', 'completed'], true);
    }
}
