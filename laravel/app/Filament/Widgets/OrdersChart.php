<?php

namespace App\Filament\Widgets;

use App\Models\OrderSnapshot;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class OrdersChart extends ChartWidget
{
    protected ?string $heading = 'Pedidos de los últimos 14 días';

    protected static ?int $sort = 2;

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $start = Carbon::today()->subDays(13);

        $counts = OrderSnapshot::query()
            ->where('date_created', '>=', $start)
            ->get(['date_created'])
            ->groupBy(fn (OrderSnapshot $order): string => $order->date_created?->format('Y-m-d') ?? '')
            ->map(fn ($group): int => $group->count());

        $labels = [];
        $values = [];

        for ($day = $start->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $labels[] = $day->format('d/m');
            $values[] = (int) ($counts[$day->format('Y-m-d')] ?? 0);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Pedidos',
                    'data' => $values,
                    'fill' => true,
                    'tension' => 0.3,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
