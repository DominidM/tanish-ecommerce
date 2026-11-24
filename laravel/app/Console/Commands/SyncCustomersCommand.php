<?php

namespace App\Console\Commands;

use App\Models\CustomerSnapshot;
use App\Services\WooCommerce\WooCommerceClient;
use Illuminate\Console\Command;

class SyncCustomersCommand extends Command
{
    protected $signature = 'woo:sync-customers';

    protected $description = 'Sincroniza los clientes de WooCommerce como snapshots de solo lectura.';

    public function handle(WooCommerceClient $woo): int
    {
        $count = 0;

        foreach ($woo->paginate('customers') as $customers) {
            foreach ($customers as $customer) {
                $id = (int) ($customer['id'] ?? 0);

                if ($id <= 0) {
                    continue;
                }

                CustomerSnapshot::updateOrCreate(
                    ['woo_id' => $id],
                    CustomerSnapshot::fromWoo($customer)
                );

                $count++;
            }
        }

        $this->info("Clientes sincronizados: {$count}");

        return self::SUCCESS;
    }
}
