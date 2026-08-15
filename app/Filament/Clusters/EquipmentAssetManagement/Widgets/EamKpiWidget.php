<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Widgets;

use App\Services\Quarry\EamAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EamKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(EamAnalyticsService::class);

        $underMaintenance = $svc->underMaintenanceCount($companyId);
        $outOfService = $svc->outOfServiceCount($companyId);
        $availability = $svc->availabilityPercent($companyId);
        $overdueMaintenance = $svc->overdueMaintenanceCount($companyId);
        $waitingParts = $svc->openWorkOrdersCount($companyId);
        $oee = $svc->oeePercent($companyId);

        return [
            Stat::make('Total Equipment', (string) $svc->totalEquipmentCount($companyId))->descriptionIcon('heroicon-m-truck')->color('info'),
            Stat::make('Running Equipment', (string) $svc->runningEquipmentCount($companyId))->descriptionIcon('heroicon-m-play-circle')->color('success'),
            Stat::make('Idle Equipment', (string) $svc->idleEquipmentCount($companyId))->descriptionIcon('heroicon-m-pause-circle')->color('gray'),
            Stat::make('Under Maintenance', (string) $underMaintenance)->descriptionIcon('heroicon-m-wrench-screwdriver')->color($underMaintenance > 0 ? 'warning' : 'success'),
            Stat::make('Out of Service', (string) $outOfService)->descriptionIcon('heroicon-m-exclamation-triangle')->color($outOfService > 0 ? 'danger' : 'success'),
            Stat::make('Equipment Availability', $availability . '%')->descriptionIcon('heroicon-m-check-circle')->color($availability >= 85 ? 'success' : ($availability >= 60 ? 'warning' : 'danger')),
            Stat::make('Equipment Utilization', $svc->utilizationPercentToday($companyId) . '%')->descriptionIcon('heroicon-m-cog-6-tooth')->color('info'),
            Stat::make('Equipment OEE', $oee . '%')->descriptionIcon('heroicon-m-chart-pie')->color($oee >= 85 ? 'success' : ($oee >= 60 ? 'warning' : 'danger')),
            Stat::make('Workshop Jobs', (string) $svc->workshopJobsCount($companyId))->descriptionIcon('heroicon-m-building-office-2')->color('info'),
            Stat::make('Open Work Orders', (string) $waitingParts)->descriptionIcon('heroicon-m-clipboard-document-list')->color($waitingParts > 0 ? 'warning' : 'success'),
            Stat::make('PM Due', (string) $svc->preventiveMaintenanceDueCount($companyId))->descriptionIcon('heroicon-m-calendar-days')->color('warning'),
            Stat::make('Overdue Maintenance', (string) $overdueMaintenance)->descriptionIcon('heroicon-m-exclamation-circle')->color($overdueMaintenance > 0 ? 'danger' : 'success'),
            Stat::make('Fuel Consumption Today', number_format($svc->fuelConsumptionTodayLitres($companyId), 0) . ' L')->descriptionIcon('heroicon-m-beaker')->color('gray'),
            Stat::make('Maintenance Cost Today', number_format($svc->maintenanceCostToday($companyId), 0) . ' ' . session('currency', 'KES'))->descriptionIcon('heroicon-m-banknotes')->color('gray'),
            Stat::make('Downtime Hours', number_format($svc->downtimeHoursToday($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-clock')->color('gray'),
            Stat::make('Average Repair Time', number_format($svc->averageRepairTimeHours($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-wrench')->color('info'),
            Stat::make('MTBF', number_format($svc->mtbfHours($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-arrow-trending-up')->color('success'),
            Stat::make('MTTR', number_format($svc->mttrHours($companyId), 1) . ' hrs')->descriptionIcon('heroicon-m-arrow-trending-down')->color('info'),
            Stat::make('Average Equipment Age', number_format($svc->averageEquipmentAgeYears($companyId), 1) . ' yrs')->descriptionIcon('heroicon-m-calendar-days')->color('gray'),
        ];
    }
}
