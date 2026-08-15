<?php

namespace App\Filament\Clusters\DispatchManagement\Widgets;

use App\Services\Quarry\DispatchAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DispatchKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(DispatchAnalyticsService::class);

        $delayed = $svc->delayedDispatchesCount($companyId);
        $pending = $svc->pendingDispatchesCount($companyId);
        $onTime = $svc->onTimeDeliveryPercent($companyId);

        return [
            Stat::make("Today's Dispatches", (string) $svc->todaysDispatchesCount($companyId))->descriptionIcon('heroicon-m-truck')->color('info'),
            Stat::make('Scheduled', (string) $svc->scheduledDispatchesCount($companyId))->descriptionIcon('heroicon-m-calendar-days')->color('gray'),
            Stat::make('Completed Today', (string) $svc->completedDispatchesTodayCount($companyId))->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Pending Approval', (string) $pending)->descriptionIcon('heroicon-m-clipboard-document-check')->color($pending > 0 ? 'warning' : 'success'),
            Stat::make('Cancelled (7d)', (string) $svc->cancelledDispatchesCount($companyId))->descriptionIcon('heroicon-m-x-circle')->color('gray'),
            Stat::make('Delayed', (string) $delayed)->descriptionIcon('heroicon-m-exclamation-triangle')->color($delayed > 0 ? 'danger' : 'success'),
            Stat::make('Trucks En Route', (string) $svc->trucksEnRouteCount($companyId))->descriptionIcon('heroicon-m-map')->color('info'),
            Stat::make('Vehicles Waiting', (string) $svc->vehiclesWaitingCount($companyId))->descriptionIcon('heroicon-m-clock')->color('warning'),
            Stat::make('Avg. Dispatch Time', number_format($svc->averageDispatchTimeMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-clock')->color('gray'),
            Stat::make('Avg. Delivery Time', number_format($svc->averageDeliveryTimeMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-map-pin')->color('gray'),
            Stat::make('Avg. Turnaround', number_format($svc->averageTurnaroundMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-arrow-path')->color('gray'),
            Stat::make("Today's Tonnes Delivered", number_format($svc->todaysTonnesDelivered($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube')->color('success'),
            Stat::make("Today's Revenue", session('currency', 'KES') . ' ' . number_format($svc->todaysRevenue($companyId), 0))->descriptionIcon('heroicon-m-banknotes')->color('success'),
            Stat::make('On-Time Delivery', $onTime . '%')->descriptionIcon('heroicon-m-check-badge')->color($onTime >= 90 ? 'success' : ($onTime >= 75 ? 'warning' : 'danger')),
        ];
    }
}
