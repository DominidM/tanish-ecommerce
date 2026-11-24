<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductSnapshot extends Model
{
    use HasFactory;

    protected $fillable = [
        'woo_id',
        'sku',
        'name',
        'slug',
        'type',
        'status',
        'price',
        'regular_price',
        'sale_price',
        'manage_stock',
        'stock_quantity',
        'stock_status',
        'category_ids',
        'image_url',
        'payload',
        'woo_created_at',
        'woo_updated_at',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'regular_price' => 'decimal:2',
            'sale_price' => 'decimal:2',
            'manage_stock' => 'boolean',
            'stock_quantity' => 'decimal:2',
            'category_ids' => 'array',
            'payload' => 'array',
            'woo_created_at' => 'datetime',
            'woo_updated_at' => 'datetime',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(InventoryMovement::class);
    }

    /**
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    public static function fromWoo(array $product): array
    {
        return [
            'sku' => $product['sku'] ?? null,
            'name' => (string) ($product['name'] ?? ''),
            'slug' => $product['slug'] ?? null,
            'type' => $product['type'] ?? null,
            'status' => $product['status'] ?? null,
            'price' => $product['price'] ?? null,
            'regular_price' => $product['regular_price'] ?? null,
            'sale_price' => filled($product['sale_price'] ?? null) ? $product['sale_price'] : null,
            'manage_stock' => (bool) ($product['manage_stock'] ?? false),
            'stock_quantity' => $product['stock_quantity'] ?? null,
            'stock_status' => $product['stock_status'] ?? null,
            'category_ids' => array_map(
                static fn (array $category): int => (int) $category['id'],
                $product['categories'] ?? []
            ),
            'image_url' => $product['images'][0]['src'] ?? null,
            'payload' => $product,
            'woo_created_at' => $product['date_created'] ?? null,
            'woo_updated_at' => $product['date_modified'] ?? null,
        ];
    }

    public function isLowStock(int $threshold = 5): bool
    {
        return $this->manage_stock
            && $this->stock_quantity !== null
            && (float) $this->stock_quantity > 0
            && (float) $this->stock_quantity <= $threshold;
    }
}
