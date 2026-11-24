<?php

namespace App\Filament\Widgets;

use App\Models\AnalyticsEvent;
use App\Models\NotificationLog;
use App\Models\OrderSnapshot;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AnalyticsStatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 3;

    protected function getStats(): array
    {
        $revenue = (float) OrderSnapshot::query()
            ->whereIn('status', ['processing', 'completed'])
            ->where('date_created', '>=', now()->startOfMonth())
            ->sum('total');

        $orders = OrderSnapshot::query()
            ->whereIn('status', ['processing', 'completed'])
            ->where('date_created', '>=', now()->startOfMonth())
            ->count();

        $average = $orders > 0 ? $revenue / $orders : 0.0;

        $gaSent = AnalyticsEvent::query()->where('status', 'sent')->count();
        $gaFailed = AnalyticsEvent::query()->where('status', 'failed')->count();
        $notificationsSent = NotificationLog::query()->where('status', 'sent')->count();
        $notificationsFailed = NotificationLog::query()->where('status', 'failed')->count();

        return [
            Stat::make('Ingresos del mes', config('tanish.currency', 'PEN').' '.number_format($revenue, 2))
                ->description("{$orders} pedidos pagados")
                ->color('success'),
            Stat::make('Ticket promedio', config('tanish.currency', 'PEN').' '.number_format($average, 2))
                ->description('Pedidos del mes')
                ->color('info'),
            Stat::make('Eventos GA4', (string) $gaSent)
                ->description($gaFailed > 0 ? "{$gaFailed} fallidos" : 'Sin fallos')
                ->color($gaFailed > 0 ? 'danger' : 'success'),
            Stat::make('Notificaciones', (string) $notificationsSent)
                ->description($notificationsFailed > 0 ? "{$notificationsFailed} fallidas" : 'Sin fallos')
                ->color($notificationsFailed > 0 ? 'danger' : 'success'),
        ];
    }
}
