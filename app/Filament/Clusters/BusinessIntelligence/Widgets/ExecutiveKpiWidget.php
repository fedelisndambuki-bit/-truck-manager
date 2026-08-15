<?php

namespace App\Filament\Clusters\BusinessIntelligence\Widgets;

use App\Services\Construction\BusinessIntelligenceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ExecutiveKpiWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');
        $bi = app(BusinessIntelligenceService::class);

        $fmt = fn (float $v) => $currency . ' ' . number_format($v, 0);
        $clientSatisfaction = $bi->clientSatisfaction($companyId);

        return [
            Stat::make('Total Projects', (string) $bi->totalProjects($companyId))->descriptionIcon('heroicon-m-building-office-2')->color('info'),
            Stat::make('Active Projects', (string) $bi->activeProjects($companyId))->descriptionIcon('heroicon-m-play-circle')->color('success'),
            Stat::make('Projects at Risk', (string) $bi->projectsAtRisk($companyId))->descriptionIcon('heroicon-m-exclamation-triangle')->color($bi->projectsAtRisk($companyId) > 0 ? 'danger' : 'success'),
            Stat::make('Total Contract Value', $fmt($bi->totalContractValue($companyId)))->descriptionIcon('heroicon-m-document-text')->color('info'),
            Stat::make('Total Revenue', $fmt($bi->totalRevenue($companyId)))->descriptionIcon('heroicon-m-arrow-trending-up')->color('success'),
            Stat::make('Total Cost', $fmt($bi->totalCost($companyId)))->descriptionIcon('heroicon-m-arrow-trending-down')->color('warning'),
            Stat::make('Profit', $fmt($bi->profit($companyId)))->descriptionIcon('heroicon-m-banknotes')->color($bi->profit($companyId) >= 0 ? 'success' : 'danger'),
            Stat::make('Profit Margin', $bi->profitMargin($companyId) . '%')->descriptionIcon('heroicon-m-chart-pie')->color($bi->profitMargin($companyId) >= 0 ? 'success' : 'danger'),
            Stat::make('Budget Utilization', $bi->budgetUtilization($companyId) . '%')->descriptionIcon('heroicon-m-scale')->color($bi->budgetUtilization($companyId) > 90 ? 'danger' : 'success'),
            Stat::make('Cash Flow Position', $fmt($bi->cashFlowPosition($companyId)))->descriptionIcon('heroicon-m-currency-dollar')->color($bi->cashFlowPosition($companyId) >= 0 ? 'success' : 'danger'),
            Stat::make('Workforce Count', (string) $bi->workforceCount($companyId))->descriptionIcon('heroicon-m-user-group')->color('info'),
            Stat::make('Equipment Utilization', $bi->equipmentUtilization($companyId) . '%')->descriptionIcon('heroicon-m-wrench-screwdriver')->color('info'),
            Stat::make('Material Cost', $fmt($bi->materialCost($companyId)))->descriptionIcon('heroicon-m-cube')->color('gray'),
            Stat::make('Safety Score', number_format($bi->safetyScore($companyId), 1))->descriptionIcon('heroicon-m-shield-check')->color($bi->safetyScore($companyId) >= 80 ? 'success' : 'danger'),
            Stat::make('Quality Score', $bi->qualityScore($companyId) . '%')->descriptionIcon('heroicon-m-check-badge')->color($bi->qualityScore($companyId) >= 80 ? 'success' : 'warning'),
            Stat::make('Client Satisfaction', $clientSatisfaction === null ? 'No ratings yet' : $clientSatisfaction . '%')->descriptionIcon('heroicon-m-face-smile')->color('info'),
            Stat::make('Project Completion Rate', $bi->projectCompletionRate($companyId) . '%')->descriptionIcon('heroicon-m-flag')->color('success'),
        ];
    }
}
