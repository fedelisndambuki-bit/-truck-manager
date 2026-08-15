<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Widgets;

use App\Services\Quarry\CrushingPlantAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class PlantKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(CrushingPlantAnalyticsService::class);

        $maintenance = $svc->plantsUnderMaintenanceCount($companyId);
        $oee = $svc->oeePercent($companyId);

        return [
            Stat::make('Active Plants', (string) $svc->activePlantsCount($companyId))->descriptionIcon('heroicon-m-building-office-2')->color('info'),
            Stat::make('Running Plants', (string) $svc->runningPlantsCount($companyId))->descriptionIcon('heroicon-m-play-circle')->color('success'),
            Stat::make('Idle Plants', (string) $svc->idlePlantsCount($companyId))->descriptionIcon('heroicon-m-pause-circle')->color('gray'),
            Stat::make('Plants Under Maintenance', (string) $maintenance)->descriptionIcon('heroicon-m-wrench-screwdriver')->color($maintenance > 0 ? 'warning' : 'success'),
            Stat::make('Production Today', number_format($svc->productionTodayTonnes($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube')->color('success'),
            Stat::make('Plant Throughput', number_format($svc->plantThroughputTph($companyId), 1) . ' t/h')->descriptionIcon('heroicon-m-chart-bar')->color('info'),
            Stat::make('Crusher Availability', $svc->crusherAvailabilityPercent($companyId) . '%')->descriptionIcon('heroicon-m-check-circle')->color('info'),
            Stat::make('Plant Utilization', $svc->plantUtilizationPercent($companyId) . '%')->descriptionIcon('heroicon-m-cog-6-tooth')->color('info'),
            Stat::make('OEE', $oee . '%')->descriptionIcon('heroicon-m-chart-pie')->color($oee >= 85 ? 'success' : ($oee >= 60 ? 'warning' : 'danger')),
            Stat::make('Operating Hours', number_format($svc->operatingHoursToday($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-clock')->color('success'),
            Stat::make('Downtime Hours', number_format($svc->downtimeHoursToday($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-exclamation-triangle')->color($svc->downtimeHoursToday($companyId) > 0 ? 'warning' : 'success'),
            Stat::make('Energy Consumption', number_format($svc->energyConsumptionToday($companyId), 0) . ' kWh')->descriptionIcon('heroicon-m-bolt')->color('gray'),
            Stat::make('Fuel Consumption', number_format($svc->fuelConsumptionToday($companyId), 0) . ' L')->descriptionIcon('heroicon-m-beaker')->color('gray'),
            Stat::make('Finished Products Produced', number_format($svc->finishedProductsToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube-transparent')->color('success'),
            Stat::make('Waste Material', number_format($svc->wasteMaterialToday($companyId), 1) . ' t')->descriptionIcon('heroicon-m-trash')->color('gray'),
            Stat::make('Recovery Rate', $svc->recoveryRatePercent($companyId) . '%')->descriptionIcon('heroicon-m-arrow-path')->color('success'),
            Stat::make('Average Throughput', number_format($svc->averageThroughput($companyId), 1) . ' t')->descriptionIcon('heroicon-m-chart-bar-square')->color('info'),
        ];
    }
}
