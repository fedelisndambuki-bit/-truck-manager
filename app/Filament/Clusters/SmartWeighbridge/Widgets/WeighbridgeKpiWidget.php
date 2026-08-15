<?php

namespace App\Filament\Clusters\SmartWeighbridge\Widgets;

use App\Services\Quarry\WeighbridgeAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WeighbridgeKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(WeighbridgeAnalyticsService::class);

        $exceptions = $svc->openExceptionsCount($companyId);
        $pending = $svc->pendingApprovalsCount($companyId);
        $accuracy = $svc->weightAccuracyPercent($companyId);
        $rejected = $svc->rejectedLoadsCount($companyId);

        return [
            Stat::make('Vehicles Waiting', (string) $svc->vehiclesWaitingCount($companyId))->descriptionIcon('heroicon-m-clock')->color('warning'),
            Stat::make('Vehicles Checked In Today', (string) $svc->vehiclesCheckedInTodayCount($companyId))->descriptionIcon('heroicon-m-identification')->color('info'),
            Stat::make('Vehicles Being Loaded', (string) $svc->vehiclesBeingLoadedCount($companyId))->descriptionIcon('heroicon-m-truck')->color('info'),
            Stat::make('Awaiting Gross Weight', (string) $svc->vehiclesAwaitingGrossWeightCount($companyId))->descriptionIcon('heroicon-m-scale')->color('warning'),
            Stat::make('Ready For Exit', (string) $svc->vehiclesReadyForExitCount($companyId))->descriptionIcon('heroicon-m-arrow-right-on-rectangle')->color('success'),
            Stat::make('Completed Dispatches Today', (string) $svc->completedDispatchesTodayCount($companyId))->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make("Today's Tonnes", number_format($svc->todaysTonnes($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube')->color('success'),
            Stat::make("Today's Revenue", session('currency', 'KES') . ' ' . number_format($svc->todaysRevenue($companyId), 0))->descriptionIcon('heroicon-m-banknotes')->color('success'),
            Stat::make('Avg. Truck Turnaround', number_format($svc->averageTruckTurnaroundMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-arrow-path')->color('info'),
            Stat::make('Avg. Waiting Time', number_format($svc->averageWaitingTimeMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-clock')->color('gray'),
            Stat::make('Avg. Loading Time', number_format($svc->averageLoadingTimeMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-cog-6-tooth')->color('gray'),
            Stat::make('Avg. Weighing Time', number_format($svc->averageWeighingTimeMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-scale')->color('gray'),
            Stat::make('Current Queue Length', (string) $svc->currentQueueLength($companyId))->descriptionIcon('heroicon-m-queue-list')->color('info'),
            Stat::make('Weight Accuracy', $accuracy . '%')->descriptionIcon('heroicon-m-check-badge')->color($accuracy >= 95 ? 'success' : ($accuracy >= 85 ? 'warning' : 'danger')),
            Stat::make('Rejected Loads', (string) $rejected)->descriptionIcon('heroicon-m-x-circle')->color($rejected > 0 ? 'danger' : 'success'),
            Stat::make('Loader Productivity Today', (string) $svc->loaderProductivityToday($companyId))->descriptionIcon('heroicon-m-user-group')->color('success'),
            Stat::make('Weighbridge Utilization', $svc->weighbridgeUtilizationPercent($companyId) . '%')->descriptionIcon('heroicon-m-chart-pie')->color('info'),
            Stat::make('Open Exceptions', (string) $exceptions)->descriptionIcon('heroicon-m-exclamation-triangle')->color($exceptions > 0 ? 'warning' : 'success'),
            Stat::make('Pending Approvals', (string) $pending)->descriptionIcon('heroicon-m-clipboard-document-check')->color($pending > 0 ? 'warning' : 'success'),
        ];
    }
}
