<?php

namespace App\Filament\Widgets;

use App\Models\OrderSnapshot;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChart extends ChartWidget
{
    protected ?string $heading = 'Ingresos de los últimos 14 días';

    protected static ?int $sort = 4;

    protected ?string $maxHeight = '320px';

    protected function getData(): array
    {
        $start = Carbon::today()->subDays(13);

        $sums = OrderSnapshot::query()
            ->whereIn('status', ['processing', 'completed'])
            ->where('date_created', '>=', $start)
            ->get(['date_created', 'total'])
            ->groupBy(fn (OrderSnapshot $order): string => $order->date_created?->format('Y-m-d') ?? '')
            ->map(fn ($group): float => (float) $group->sum('total'));

        $labels = [];
        $values = [];

        for ($day = $start->copy(); $day->lte(Carbon::today()); $day->addDay()) {
            $labels[] = $day->format('d/m');
            $values[] = round((float) ($sums[$day->format('Y-m-d')] ?? 0), 2);
        }

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos ('.config('tanish.currency', 'PEN').')',
                    'data' => $values,
                    'backgroundColor' => 'rgba(245, 158, 11, 0.6)',
                    'borderColor' => 'rgb(245, 158, 11)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
