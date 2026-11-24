<?php

namespace App\Services\Inventory;

use App\Exceptions\InventoryException;
use App\Models\InventoryMovement;
use App\Models\ProductSnapshot;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class InventoryService
{
    public function __construct(private readonly WooCommerceClient $woo) {}

    /**
     * Aplica un movimiento manual y sincroniza el stock en WooCommerce.
     *
     * @param  array<string, mixed>  $metadata
     */
    public function apply(
        int $wooProductId,
        string $type,
        float $quantity,
        string $reason,
        ?int $userId = null,
        string $source = InventoryMovement::SOURCE_MANUAL,
        ?string $reference = null,
        array $metadata = [],
    ): InventoryMovement {
        $this->assertValid($type, $quantity, $reason);

        return $this->withStockLock($wooProductId, function () use (
            $wooProductId, $type, $quantity, $reason, $userId, $source, $reference, $metadata
        ): InventoryMovement {
            $product = $this->woo->product($wooProductId);

            if (! ($product['manage_stock'] ?? false)) {
                throw InventoryException::invalidQuantity(
                    'El producto no tiene activada la gestión de stock en WooCommerce.'
                );
            }

            $stockBefore = (float) ($product['stock_quantity'] ?? 0);

            $stockAfter = match ($type) {
                InventoryMovement::TYPE_ENTRY => $stockBefore + $quantity,
                InventoryMovement::TYPE_EXIT => $stockBefore - $quantity,
                InventoryMovement::TYPE_ADJUSTMENT => $quantity,
            };

            if ($stockAfter < 0) {
                throw InventoryException::insufficientStock($stockBefore, $quantity);
            }

            // WooCommerce sigue siendo la fuente de verdad del stock.
            $this->woo->updateStock($wooProductId, $stockAfter, true);

            return DB::transaction(function () use (
                $product, $wooProductId, $type, $stockBefore, $stockAfter, $reason, $userId, $source, $reference, $metadata
            ): InventoryMovement {
                $snapshot = $this->persistProduct($product, $stockAfter);

                return InventoryMovement::firstOrCreate(
                    [
                        'source' => $source,
                        'reference' => $reference,
                        'woo_product_id' => $wooProductId,
                        'movement_type' => $type,
                    ],
                    [
                        'product_snapshot_id' => $snapshot->id,
                        'quantity_change' => $stockAfter - $stockBefore,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reason' => $reason,
                        'user_id' => $userId,
                        'metadata' => $metadata,
                    ]
                );
            });
        });
    }

    /**
     * Registra una salida observada por una venta. WooCommerce ya descontó el
     * stock; aquí solo se documenta el movimiento para el kardex.
     */
    public function recordSale(int $wooOrderId, int $wooProductId, float $quantity, ?int $wooItemId = null): InventoryMovement
    {
        if ($quantity <= 0) {
            throw InventoryException::invalidQuantity('La cantidad vendida debe ser mayor que cero.');
        }

        $reference = sprintf('woo-order:%d:item:%s', $wooOrderId, $wooItemId ?? $wooProductId);

        return $this->withStockLock($wooProductId, function () use (
            $wooOrderId, $wooProductId, $quantity, $reference, $wooItemId
        ): InventoryMovement {
            $existing = InventoryMovement::where([
                'source' => InventoryMovement::SOURCE_ORDER,
                'reference' => $reference,
                'woo_product_id' => $wooProductId,
                'movement_type' => InventoryMovement::TYPE_EXIT,
            ])->first();

            if ($existing) {
                return $existing;
            }

            $product = $this->woo->product($wooProductId);
            $stockAfter = (float) ($product['stock_quantity'] ?? 0);
            $stockBefore = $stockAfter + $quantity;

            return DB::transaction(function () use (
                $product, $wooOrderId, $wooProductId, $reference, $wooItemId, $quantity, $stockBefore, $stockAfter
            ): InventoryMovement {
                $snapshot = $this->persistProduct($product, $stockAfter);

                return InventoryMovement::firstOrCreate(
                    [
                        'source' => InventoryMovement::SOURCE_ORDER,
                        'reference' => $reference,
                        'woo_product_id' => $wooProductId,
                        'movement_type' => InventoryMovement::TYPE_EXIT,
                    ],
                    [
                        'product_snapshot_id' => $snapshot->id,
                        'quantity_change' => -1 * $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reason' => "Venta en pedido WooCommerce #{$wooOrderId}",
                        'user_id' => null,
                        'metadata' => [
                            'woo_order_id' => $wooOrderId,
                            'woo_item_id' => $wooItemId,
                        ],
                    ]
                );
            });
        });
    }

    /**
     * Registra la reposición observada al cancelar un pedido. No modifica el
     * stock: WooCommerce ya lo restauró (o no) según su propia configuración.
     */
    public function recordRestoration(int $wooOrderId, int $wooProductId, float $quantity, ?int $wooItemId = null): ?InventoryMovement
    {
        $reference = sprintf('woo-order:%d:item:%s', $wooOrderId, $wooItemId ?? $wooProductId);

        return $this->withStockLock($wooProductId, function () use (
            $wooOrderId, $wooProductId, $quantity, $reference, $wooItemId
        ): ?InventoryMovement {
            $existing = InventoryMovement::where([
                'source' => InventoryMovement::SOURCE_REVERSAL,
                'reference' => $reference,
                'woo_product_id' => $wooProductId,
                'movement_type' => InventoryMovement::TYPE_ENTRY,
            ])->first();

            if ($existing) {
                return $existing;
            }

            $product = $this->woo->product($wooProductId);
            $stockAfter = (float) ($product['stock_quantity'] ?? 0);
            $stockBefore = $stockAfter - $quantity;

            if ($stockBefore < 0) {
                return null;
            }

            return DB::transaction(function () use (
                $product, $wooOrderId, $wooProductId, $reference, $wooItemId, $quantity, $stockBefore, $stockAfter
            ): InventoryMovement {
                $snapshot = $this->persistProduct($product, $stockAfter);

                return InventoryMovement::firstOrCreate(
                    [
                        'source' => InventoryMovement::SOURCE_REVERSAL,
                        'reference' => $reference,
                        'woo_product_id' => $wooProductId,
                        'movement_type' => InventoryMovement::TYPE_ENTRY,
                    ],
                    [
                        'product_snapshot_id' => $snapshot->id,
                        'quantity_change' => $quantity,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'reason' => "Reversión por cancelación del pedido WooCommerce #{$wooOrderId}",
                        'user_id' => null,
                        'metadata' => [
                            'woo_order_id' => $wooOrderId,
                            'woo_item_id' => $wooItemId,
                        ],
                    ]
                );
            });
        });
    }

    private function assertValid(string $type, float $quantity, string $reason): void
    {
        if (! in_array($type, [
            InventoryMovement::TYPE_ENTRY,
            InventoryMovement::TYPE_EXIT,
            InventoryMovement::TYPE_ADJUSTMENT,
        ], true)) {
            throw InventoryException::invalidType($type);
        }

        if ($type !== InventoryMovement::TYPE_ADJUSTMENT && $quantity <= 0) {
            throw InventoryException::invalidQuantity('La cantidad debe ser mayor que cero.');
        }

        if ($type === InventoryMovement::TYPE_ADJUSTMENT && $quantity < 0) {
            throw InventoryException::invalidQuantity('El stock ajustado no puede ser negativo.');
        }

        if (trim($reason) === '') {
            throw InventoryException::missingReason();
        }
    }

    /**
     * Serializa las operaciones de stock por producto.
     */
    private function withStockLock(int $wooProductId, callable $callback): mixed
    {
        return Cache::lock("tanish:stock:{$wooProductId}", 15)->block(10, $callback);
    }

    private function persistProduct(array $product, float $stockAfter): ProductSnapshot
    {
        $data = ProductSnapshot::fromWoo($product);
        $data['stock_quantity'] = $stockAfter;

        return ProductSnapshot::updateOrCreate(['woo_id' => (int) $product['id']], $data);
    }
}
