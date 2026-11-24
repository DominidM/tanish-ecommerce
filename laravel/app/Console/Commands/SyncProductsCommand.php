<?php

namespace App\Console\Commands;

use App\Models\ProductSnapshot;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Console\Command;

class SyncProductsCommand extends Command
{
    protected $signature = 'woo:sync-products {--status=any : Estado de WooCommerce a sincronizar}';

    protected $description = 'Sincroniza los productos de WooCommerce como snapshots de solo lectura.';

    public function handle(WooCommerceClient $woo): int
    {
        $count = 0;

        foreach ($woo->paginate('products', ['status' => (string) $this->option('status')]) as $products) {
            foreach ($products as $product) {
                $id = (int) ($product['id'] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                ProductSnapshot::updateOrCreate(
                    ['woo_id' => $id],
                    ProductSnapshot::fromWoo($product)
                );

                $count++;
            }
        }

        $this->info("Productos sincronizados: {$count}");

        return self::SUCCESS;
    }
}
