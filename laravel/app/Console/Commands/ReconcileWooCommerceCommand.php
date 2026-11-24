<?php

namespace App\Console\Commands;

use App\Models\OrderSnapshot;
use App\Models\ProductSnapshot;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Console\Command;

class ReconcileWooCommerceCommand extends Command
{
    protected $signature = 'woo:reconcile {--days=7 : Ventana de reconciliación de pedidos}';

    protected $description = 'Reconcilia snapshots y detecta pedidos pagados sin movimientos de stock.';

    public function handle(WooCommerceClient $woo): int
    {
        $this->call('woo:sync-products');
        $this->call('woo:sync-orders', ['--days' => (int) $this->option('days')]);
        $this->call('woo:sync-customers');

        $this->verifyStock($woo);

        $pending = OrderSnapshot::query()
            ->whereIn('status', ['processing', 'completed'])
            ->whereNull('stock_recorded_at')
            ->count();

        if ($pending > 0) {
            $this->warn("Pedidos pagados sin movimientos de stock registrados: {$pending}. Requieren revisión.");
        } else {
            $this->info('No hay pedidos pagados pendientes de registrar stock.');
        }

        return self::SUCCESS;
    }

    private function verifyStock(WooCommerceClient $woo): void
    {
        $discrepancies = 0;

        ProductSnapshot::query()
            ->where('manage_stock', true)
            ->each(function (ProductSnapshot $snapshot) use ($woo, &$discrepancies): void {
                $product = $woo->product((int) $snapshot->woo_id);
                $remote = $product['stock_quantity'] ?? null;
                $local = $snapshot->stock_quantity;

                if ($remote === null || $local === null) {
                    return;
                }

                if ((float) $remote !== (float) $local) {
                    $discrepancies++;
                    $this->warn(sprintf(
                        'Producto %s (#%d): stock local %s, stock WooCommerce %s',
                        $snapshot->name,
                        $snapshot->woo_id,
                        $local,
                        $remote
                    ));
                }
            });

        $this->info("Discrepancias de stock detectadas: {$discrepancies}");
    }
}
