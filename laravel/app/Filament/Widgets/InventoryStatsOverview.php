<?php

namespace App\Filament\Widgets;

use App\Models\InventoryMovement;
use App\Models\OrderSnapshot;
use App\Models\ProductSnapshot;
use App\Models\WebhookEvent;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class InventoryStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $products = ProductSnapshot::query()->count();

        $lowStock = ProductSnapshot::query()
            ->where('manage_stock', true)
            ->where('stock_quantity', '>', 0)
            ->where('stock_quantity', '<=', 5)
            ->count();

        $outOfStock = ProductSnapshot::query()
            ->where('manage_stock', true)
            ->where('stock_quantity', '<=', 0)
            ->count();

        $ordersToday = OrderSnapshot::query()
            ->whereDate('date_created', today())
            ->count();

        $movementsToday = InventoryMovement::query()
            ->whereDate('created_at', today())
            ->count();

        $failedWebhooks = WebhookEvent::query()
            ->where('status', 'failed')
            ->count();

        return [
            Stat::make('Productos sincronizados', (string) $products)
                ->description('Catálogo leído de WooCommerce')
                ->color('info'),
            Stat::make('Stock bajo', (string) $lowStock)
                ->description("{$outOfStock} sin stock")
                ->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make('Pedidos de hoy', (string) $ordersToday)
                ->description('Creados en WooCommerce')
                ->color('primary'),
            Stat::make('Movimientos de hoy', (string) $movementsToday)
                ->description('Kardex del servicio Laravel')
                ->color('success'),
            Stat::make('Webhooks fallidos', (string) $failedWebhooks)
                ->description($failedWebhooks > 0 ? 'Requieren revisión' : 'Sin incidencias')
                ->color($failedWebhooks > 0 ? 'danger' : 'success'),
        ];
    }
}
