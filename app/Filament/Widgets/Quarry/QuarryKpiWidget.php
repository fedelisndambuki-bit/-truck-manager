<?php

namespace App\Filament\Widgets\Quarry;

use App\Services\Quarry\QuarryAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuarryKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');
        $qa = app(QuarryAnalyticsService::class);
        $fmt = fn (float $v) => $currency . ' ' . number_format($v, 0);

        $breakdowns = $qa->breakdownsCount($companyId);
        $maintenanceDue = $qa->maintenanceDueCount($companyId);
        $incidents = $qa->openSafetyIncidents($companyId);
        $lowStock = $qa->lowStockpileCount($companyId);

        return [
            Stat::make('Production Today', number_format($qa->productionToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube')->color('success'),
            Stat::make('Production This Month', number_format($qa->productionMonth($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube-transparent')->color('info'),
            Stat::make('Target Achievement', $qa->targetAchievementPercent($companyId) . '%')->descriptionIcon('heroicon-m-flag')->color($qa->targetAchievementPercent($companyId) >= 90 ? 'success' : 'warning'),
            Stat::make('Crusher Utilization', $qa->crusherUtilizationPercent($companyId) . '%')->descriptionIcon('heroicon-m-cog-6-tooth')->color('info'),
            Stat::make('Plant Availability', $qa->plantAvailabilityPercent($companyId) . '%')->descriptionIcon('heroicon-m-check-circle')->color($qa->plantAvailabilityPercent($companyId) >= 80 ? 'success' : 'warning'),
            Stat::make('Equipment Utilization', $qa->equipmentUtilizationPercent($companyId) . '%')->descriptionIcon('heroicon-m-wrench-screwdriver')->color('info'),
            Stat::make('Stock Available', number_format($qa->stockAvailableTonnes($companyId), 1) . ' t')->descriptionIcon('heroicon-m-archive-box')->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make('Stock Value', $fmt($qa->stockValue($companyId)))->descriptionIcon('heroicon-m-banknotes')->color('gray'),
            Stat::make('Dispatches Today', (string) $qa->dispatchesToday($companyId))->descriptionIcon('heroicon-m-truck')->color('info'),
            Stat::make('Orders Pending', (string) $qa->ordersPending($companyId))->descriptionIcon('heroicon-m-clock')->color('warning'),
            Stat::make('Orders Completed', (string) $qa->ordersCompleted($companyId))->descriptionIcon('heroicon-m-check-badge')->color('success'),
            Stat::make('Revenue Today', $fmt($qa->revenueToday($companyId)))->descriptionIcon('heroicon-m-currency-dollar')->color('success'),
            Stat::make('Revenue This Month', $fmt($qa->revenueMonth($companyId)))->descriptionIcon('heroicon-m-arrow-trending-up')->color('success'),
            Stat::make('Fuel Consumed Today', number_format($qa->fuelConsumedToday($companyId), 0) . ' L')->descriptionIcon('heroicon-m-beaker')->color('gray'),
            Stat::make('Fuel Stock', number_format($qa->fuelStockLitres($companyId), 0) . ' L')->descriptionIcon('heroicon-m-battery-50')->color('gray'),
            Stat::make('Maintenance Due', (string) $maintenanceDue)->descriptionIcon('heroicon-m-wrench')->color($maintenanceDue > 0 ? 'warning' : 'success'),
            Stat::make('Breakdowns', (string) $breakdowns)->descriptionIcon('heroicon-m-exclamation-circle')->color($breakdowns > 0 ? 'danger' : 'success'),
            Stat::make('Avg Truck Turnaround', number_format($qa->avgTruckTurnaroundMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-arrow-path')->color('info'),
            Stat::make('Safety Incidents (Open)', (string) $incidents)->descriptionIcon('heroicon-m-shield-exclamation')->color($incidents > 0 ? 'danger' : 'success'),
        ];
    }
}
