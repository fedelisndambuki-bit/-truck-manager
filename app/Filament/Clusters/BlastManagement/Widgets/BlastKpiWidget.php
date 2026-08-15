<?php

namespace App\Filament\Clusters\BlastManagement\Widgets;

use App\Services\Quarry\BlastAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class BlastKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(BlastAnalyticsService::class);

        $delays = $svc->blastDelaysCount($companyId);
        $misfires = $svc->misfiresCount($companyId);
        $safetyIncidents = $svc->safetyIncidentsFromBlasts($companyId);
        $envAlerts = $svc->environmentalAlertsCount($companyId);

        return [
            Stat::make('Scheduled Blasts', (string) $svc->scheduledBlastsCount($companyId))->descriptionIcon('heroicon-m-calendar-days')->color('info'),
            Stat::make('Completed Blasts', (string) $svc->completedBlastsCount($companyId))->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Pending Approvals', (string) $svc->pendingApprovalsCount($companyId))->descriptionIcon('heroicon-m-clock')->color('warning'),
            Stat::make('Blast Success Rate', $svc->blastSuccessRatePercent($companyId) . '%')->descriptionIcon('heroicon-m-trophy')->color($svc->blastSuccessRatePercent($companyId) >= 90 ? 'success' : 'warning'),
            Stat::make('Avg Fragmentation Size', number_format($svc->avgFragmentationSize($companyId), 1) . ' cm')->descriptionIcon('heroicon-m-squares-plus')->color('info'),
            Stat::make('Explosives Used', number_format($svc->explosivesUsedTotal($companyId), 0) . ' kg')->descriptionIcon('heroicon-m-beaker')->color('gray'),
            Stat::make('Drilled Holes', (string) $svc->drilledHolesCount($companyId))->descriptionIcon('heroicon-m-cog-8-tooth')->color('info'),
            Stat::make('Blast Delays', (string) $delays)->descriptionIcon('heroicon-m-pause-circle')->color($delays > 0 ? 'warning' : 'success'),
            Stat::make('Misfires', (string) $misfires)->descriptionIcon('heroicon-m-exclamation-triangle')->color($misfires > 0 ? 'danger' : 'success'),
            Stat::make('Safety Incidents', (string) $safetyIncidents)->descriptionIcon('heroicon-m-shield-exclamation')->color($safetyIncidents > 0 ? 'danger' : 'success'),
            Stat::make('Environmental Alerts', (string) $envAlerts)->descriptionIcon('heroicon-m-globe-alt')->color($envAlerts > 0 ? 'danger' : 'success'),
            Stat::make('Production Yield From Blasts', number_format($svc->productionYieldFromBlasts($companyId), 1) . ' t')->descriptionIcon('heroicon-m-cube')->color('success'),
        ];
    }
}
