<?php

namespace Database\Seeders;

use App\Models\AnalyticsEvent;
use App\Models\InventoryMovement;
use App\Models\NotificationLog;
use App\Models\OrderSnapshot;
use App\Models\ProductSnapshot;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        self::purge();

        $products = ProductSnapshot::query()
            ->where('status', 'publish')
            ->orderBy('id')
            ->limit(4)
            ->get();

        if ($products->isEmpty()) {
            $this->command?->warn('No hay productos publicados; ejecuta woo:sync-products primero.');

            return;
        }

        $admin = null;

        if (Schema::hasColumn('users', 'username')) {
            $admin = User::query()->where('username', 'tanish_admin')->first();
        }

        $admin ??= User::query()->first();

        $today = Carbon::today();

        for ($i = 0; $i < 5; $i++) {
            $product = $products[$i % $products->count()];
            $quantity = ($i % 2) + 1;
            $total = (float) $product->price * $quantity;
            $date = $today->copy()->subDays(12 - ($i * 2));
            $orderId = 800001 + $i;

            $order = OrderSnapshot::create([
                'woo_id' => $orderId,
                'number' => 'DEMO-'.($i + 1),
                'status' => $i % 3 === 0 ? 'completed' : 'processing',
                'currency' => 'PEN',
                'total' => $total,
                'customer_id' => null,
                'customer_name' => 'Cliente Demo '.($i + 1),
                'customer_email' => 'demo'.($i + 1).'@tanish.test',
                'payment_method' => 'demo',
                'payment_method_title' => 'Pago demo',
                'date_created' => $date,
                'date_paid' => $date,
                'line_items' => [[
                    'id' => 810001 + $i,
                    'product_id' => $product->woo_id,
                    'name' => $product->name,
                    'quantity' => $quantity,
                    'sku' => $product->sku,
                    'total' => $total,
                ]],
                'payload' => ['demo' => true],
                'stock_recorded_at' => $date,
            ]);

            $movement = InventoryMovement::create([
                'product_snapshot_id' => $product->id,
                'woo_product_id' => $product->woo_id,
                'movement_type' => InventoryMovement::TYPE_EXIT,
                'quantity_change' => -1 * $quantity,
                'stock_before' => (float) $product->stock_quantity + $quantity,
                'stock_after' => (float) $product->stock_quantity,
                'reason' => 'Venta demo '.$order->number,
                'user_id' => $admin?->id,
                'source' => 'demo',
                'reference' => 'demo:order:'.$orderId,
                'metadata' => ['demo' => true, 'woo_order_id' => $orderId],
            ]);
            $movement->timestamps = false;
            $movement->forceFill(['created_at' => $date, 'updated_at' => $date])->save();

            AnalyticsEvent::create([
                'name' => 'purchase',
                'event_id' => 'demo:purchase:'.$orderId,
                'woo_order_id' => $orderId,
                'payload' => [
                    'transaction_id' => (string) $orderId,
                    'value' => $total,
                    'currency' => 'PEN',
                ],
                'status' => $i % 2 === 0 ? 'sent' : 'skipped',
                'sent_at' => $i % 2 === 0 ? $date : null,
            ]);

            NotificationLog::create([
                'channel' => 'whatsapp',
                'recipient' => '5199999999'.$i,
                'template' => 'demo',
                'payload' => [
                    'order_id' => $orderId,
                    'order_number' => 'DEMO-'.($i + 1),
                    'total' => $total,
                    'customer' => 'Cliente Demo '.($i + 1),
                ],
                'status' => $i % 2 === 0 ? 'sent' : 'skipped',
                'attempts' => 1,
                'sent_at' => $i % 2 === 0 ? $date : null,
            ]);
        }

        // Movimientos manuales de demostración sobre el primer producto.
        $product = $products->first();
        $stock = (float) $product->stock_quantity;

        InventoryMovement::create([
            'product_snapshot_id' => $product->id,
            'woo_product_id' => $product->woo_id,
            'movement_type' => InventoryMovement::TYPE_ENTRY,
            'quantity_change' => 5,
            'stock_before' => $stock,
            'stock_after' => $stock + 5,
            'reason' => 'Compra demo a proveedor',
            'user_id' => $admin?->id,
            'source' => 'demo',
            'reference' => 'demo:manual:entry',
            'metadata' => ['demo' => true],
        ]);

        InventoryMovement::create([
            'product_snapshot_id' => $product->id,
            'woo_product_id' => $product->woo_id,
            'movement_type' => InventoryMovement::TYPE_EXIT,
            'quantity_change' => -2,
            'stock_before' => $stock + 5,
            'stock_after' => $stock + 3,
            'reason' => 'Salida demo por merma',
            'user_id' => $admin?->id,
            'source' => 'demo',
            'reference' => 'demo:manual:exit',
            'metadata' => ['demo' => true],
        ]);

        InventoryMovement::create([
            'product_snapshot_id' => $product->id,
            'woo_product_id' => $product->woo_id,
            'movement_type' => InventoryMovement::TYPE_ADJUSTMENT,
            'quantity_change' => 0,
            'stock_before' => $stock + 3,
            'stock_after' => $stock + 3,
            'reason' => 'Ajuste demo por conteo físico',
            'user_id' => $admin?->id,
            'source' => 'demo',
            'reference' => 'demo:manual:adjustment',
            'metadata' => ['demo' => true],
        ]);

        foreach (['processed', 'processed', 'failed'] as $index => $status) {
            WebhookEvent::create([
                'delivery_id' => 'demo-delivery-'.($index + 1),
                'payload_hash' => hash('sha256', 'demo-webhook-'.($index + 1)),
                'topic' => $index === 2 ? 'product.updated' : 'order.updated',
                'resource' => $index === 2 ? 'product' : 'order',
                'event_action' => 'updated',
                'source' => 'demo',
                'payload' => [
                    'id' => $index === 2 ? $products->first()->woo_id : 800001 + $index,
                    'demo' => true,
                ],
                'status' => $status,
                'attempts' => 1,
                'last_error' => $status === 'failed' ? 'Evento de demostración' : null,
                'received_at' => $today->copy()->subMinutes(30 - $index),
                'processed_at' => $status === 'processed' ? $today->copy()->subMinutes(29 - $index) : null,
            ]);
        }

        $this->command?->info('Datos demo insertados. Bórralos con: php artisan demo:purge');
    }

    public static function purge(): void
    {
        InventoryMovement::query()->where('source', 'demo')->delete();
        OrderSnapshot::query()->whereBetween('woo_id', [800001, 800099])->delete();
        AnalyticsEvent::query()->where('event_id', 'like', 'demo:%')->delete();
        NotificationLog::query()->where('template', 'demo')->delete();
        WebhookEvent::query()->where('delivery_id', 'like', 'demo-%')->delete();
    }
}
