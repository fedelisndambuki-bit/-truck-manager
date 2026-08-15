<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Widgets;

use App\Services\Quarry\LabAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LabKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(LabAnalyticsService::class);

        $onHold = $svc->productsOnHoldCount($companyId);
        $ncrs = $svc->openNonConformancesCount($companyId);
        $capas = $svc->correctiveActionsCount($companyId);
        $passRate = $svc->qualityPassRatePercent($companyId);

        return [
            Stat::make('Samples Received Today', (string) $svc->samplesReceivedTodayCount($companyId))->descriptionIcon('heroicon-m-beaker')->color('info'),
            Stat::make('Samples Tested', (string) $svc->samplesTestedCount($companyId))->descriptionIcon('heroicon-m-clipboard-document-check')->color('info'),
            Stat::make('Pending Tests', (string) $svc->pendingTestsCount($companyId))->descriptionIcon('heroicon-m-clock')->color('warning'),
            Stat::make('Approved Products', (string) $svc->approvedProductsCount($companyId))->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Rejected Products', (string) $svc->rejectedProductsCount($companyId))->descriptionIcon('heroicon-m-x-circle')->color('danger'),
            Stat::make('Products On Hold', (string) $onHold)->descriptionIcon('heroicon-m-lock-closed')->color($onHold > 0 ? 'warning' : 'success'),
            Stat::make('Avg. Test Time', number_format($svc->averageTestTimeMinutes($companyId), 0) . ' min')->descriptionIcon('heroicon-m-clock')->color('gray'),
            Stat::make('Lab Utilization', $svc->laboratoryUtilizationPercent($companyId) . '%')->descriptionIcon('heroicon-m-chart-pie')->color('info'),
            Stat::make('Quality Pass Rate', $passRate . '%')->descriptionIcon('heroicon-m-check-badge')->color($passRate >= 90 ? 'success' : ($passRate >= 75 ? 'warning' : 'danger')),
            Stat::make('Quality Fail Rate', $svc->qualityFailRatePercent($companyId) . '%')->descriptionIcon('heroicon-m-x-circle')->color('gray'),
            Stat::make('Open Non-Conformances', (string) $ncrs)->descriptionIcon('heroicon-m-exclamation-triangle')->color($ncrs > 0 ? 'warning' : 'success'),
            Stat::make('Corrective Actions', (string) $capas)->descriptionIcon('heroicon-m-wrench-screwdriver')->color($capas > 0 ? 'warning' : 'success'),
            Stat::make('Customer Complaints', (string) $svc->customerComplaintsCount($companyId))->descriptionIcon('heroicon-m-face-frown')->color('gray'),
            Stat::make('Certificates Issued', (string) $svc->certificatesIssuedCount($companyId))->descriptionIcon('heroicon-m-document-check')->color('success'),
        ];
    }
}
