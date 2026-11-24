<?php

namespace App\Console\Commands;

use App\Models\OrderSnapshot;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SyncOrdersCommand extends Command
{
    protected $signature = 'woo:sync-orders
        {--days=0 : Sincroniza pedidos de los últimos N días (0 = todos)}
        {--status=any : Estado de WooCommerce a sincronizar}';

    protected $description = 'Sincroniza los pedidos de WooCommerce como snapshots de solo lectura.';

    public function handle(WooCommerceClient $woo): int
    {
        $query = ['status' => (string) $this->option('status')];
        $days = (int) $this->option('days');

        if ($days > 0) {
            $query['after'] = Carbon::now()->subDays($days)->toIso8601String();
        }

        $count = 0;

        foreach ($woo->paginate('orders', $query) as $orders) {
            foreach ($orders as $order) {
                $id = (int) ($order['id'] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                OrderSnapshot::updateOrCreate(
                    ['woo_id' => $id],
                    OrderSnapshot::fromWoo($order)
                );

                $count++;
            }
        }

        $this->info("Pedidos sincronizados: {$count}");

        return self::SUCCESS;
    }
}
