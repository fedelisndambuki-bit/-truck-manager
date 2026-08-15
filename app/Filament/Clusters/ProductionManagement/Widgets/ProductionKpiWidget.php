<?php

namespace App\Filament\Clusters\ProductionManagement\Widgets;

use App\Services\Quarry\QuarryAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ProductionKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $qa = app(QuarryAnalyticsService::class);

        return [
            Stat::make('Production Today', number_format($qa->productionToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube')->color('success'),
            Stat::make('Production This Week', number_format($qa->productionWeek($companyId), 1) . ' t')->descriptionIcon('heroicon-m-calendar-days')->color('info'),
            Stat::make('Production This Month', number_format($qa->productionMonth($companyId), 1) . ' t')->descriptionIcon('heroicon-m-calendar')->color('info'),
            Stat::make('Target Achievement', $qa->targetAchievementPercent($companyId) . '%')->descriptionIcon('heroicon-m-flag')->color($qa->targetAchievementPercent($companyId) >= 90 ? 'success' : 'warning'),
            Stat::make('Raw Material Extracted', number_format($qa->productionToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-beaker')->color('gray'),
            Stat::make('Finished Products Produced', number_format($qa->finishedProductsToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube-transparent')->color('success'),
            Stat::make('Crusher Feed', number_format($qa->feedQuantityToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-arrow-down-circle')->color('gray'),
            Stat::make('Crusher Output', number_format($qa->crusherOutputToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-arrow-up-circle')->color('gray'),
            Stat::make('Yield %', $qa->yieldPercent($companyId) . '%')->descriptionIcon('heroicon-m-chart-pie')->color($qa->yieldPercent($companyId) >= 70 ? 'success' : 'warning'),
            Stat::make('Waste %', $qa->wastePercent($companyId) . '%')->descriptionIcon('heroicon-m-trash')->color($qa->wastePercent($companyId) <= 10 ? 'success' : 'danger'),
            Stat::make('Plant Utilization', $qa->plantUtilizationPercent($companyId) . '%')->descriptionIcon('heroicon-m-cog-6-tooth')->color('info'),
            Stat::make('Shift Efficiency', $qa->shiftEfficiencyPercent($companyId) . '%')->descriptionIcon('heroicon-m-bolt')->color($qa->shiftEfficiencyPercent($companyId) >= 80 ? 'success' : 'warning'),
            Stat::make('Avg Production Rate', number_format($qa->avgProductionRate($companyId), 1) . ' t/hr')->descriptionIcon('heroicon-m-chart-bar')->color('info'),
            Stat::make('Operating Hours', number_format($qa->operatingHoursToday($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-clock')->color('success'),
            Stat::make('Downtime Hours', number_format($qa->downtimeHoursToday($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-pause-circle')->color($qa->downtimeHoursToday($companyId) > 0 ? 'danger' : 'success'),
        ];
    }
}
