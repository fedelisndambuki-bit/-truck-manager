<?php

namespace App\Filament\Clusters\StockpileManagement\Widgets;

use App\Services\Quarry\StockpileAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class StockpileKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(StockpileAnalyticsService::class);

        $low = $svc->lowStockCount($companyId);
        $critical = $svc->criticalStockCount($companyId);
        $awaitingQuality = $svc->productsAwaitingQualityApprovalCount($companyId);
        $accuracy = $svc->inventoryAccuracyPercent($companyId);

        return [
            Stat::make('Total Stock Available', number_format($svc->totalStockAvailable($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube')->color('success'),
            Stat::make('Stock Value', session('currency', 'KES') . ' ' . number_format($svc->stockValue($companyId), 0))->descriptionIcon('heroicon-m-banknotes')->color('info'),
            Stat::make('Reserved Stock', number_format($svc->reservedStock($companyId), 1) . ' t')->descriptionIcon('heroicon-m-bookmark')->color('warning'),
            Stat::make('Available Stock', number_format($svc->availableStock($companyId), 1) . ' t')->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make("Today's Production Received", number_format($svc->productionReceivedToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-inbox-arrow-down')->color('success'),
            Stat::make("Today's Dispatches", number_format($svc->dispatchesToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-truck')->color('info'),
            Stat::make("Today's Transfers", (string) $svc->transfersToday($companyId))->descriptionIcon('heroicon-m-arrow-path')->color('info'),
            Stat::make("Today's Adjustments", (string) $svc->adjustmentsToday($companyId))->descriptionIcon('heroicon-m-adjustments-horizontal')->color('gray'),
            Stat::make('Low Stock Alerts', (string) $low)->descriptionIcon('heroicon-m-exclamation-triangle')->color($low > 0 ? 'warning' : 'success'),
            Stat::make('Critical Stock Alerts', (string) $critical)->descriptionIcon('heroicon-m-exclamation-circle')->color($critical > 0 ? 'danger' : 'success'),
            Stat::make('Awaiting Quality Approval', (string) $awaitingQuality)->descriptionIcon('heroicon-m-beaker')->color($awaitingQuality > 0 ? 'warning' : 'success'),
            Stat::make('Inventory Accuracy', $accuracy . '%')->descriptionIcon('heroicon-m-chart-pie')->color($accuracy >= 95 ? 'success' : ($accuracy >= 85 ? 'warning' : 'danger')),
        ];
    }
}
