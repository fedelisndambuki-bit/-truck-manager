<?php

namespace App\Filament\Pages;

use App\Enums\CompanyIndustry;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Support\Facades\Auth;

class Dashboard extends BaseDashboard
{
    protected static string $view = 'filament.admin.pages.dashboard';

    /**
     * Construction companies get their own dedicated dashboard (Construction
     * > Dashboard), so this generic one — which still mixes in unrelated
     * financial/fleet widgets — is hidden for them to avoid two conflicting
     * "Dashboard" entries in the sidebar.
     */
    public static function shouldRegisterNavigation(): bool
    {
        $industry = Auth::user()?->company?->industry;

        return $industry !== CompanyIndustry::Construction->value;
    }

    public function getWidgets(): array
    {
        $industry = Auth::user()?->company?->industry ?? CompanyIndustry::Trucking->value;

        // Industry-specific widgets are split by type so they slot in next to
        // their common counterparts (all KPI stat cards together, then all
        // charts, then tables) instead of being bolted on as one trailing
        // block, which pushed them below the fold as unloaded blank cards.
        // $industryLeadStat is the industry's own operational overview (e.g.
        // Total Trucks / Active Trips / Under Maintenance / Monthly Fuel for
        // trucking) and opens the dashboard, ahead of the financial cards.
        [$industryLeadStat, $industryStats, $industryCharts, $industryOther, $industryTables] = match ($industry) {
            CompanyIndustry::Construction->value => [
                \App\Filament\Widgets\EquipmentOverviewWidget::class,
                [],
                [],
                [],
                [\App\Filament\Widgets\ActiveProjectsWidget::class],
            ],
            CompanyIndustry::Quarry->value => [
                \App\Filament\Widgets\ProductionOverviewWidget::class,
                [],
                [],
                [\App\Filament\Clusters\BusinessIntelligence\Widgets\AiInsightsWidget::class],
                [\App\Filament\Widgets\ExtractionSiteStatusWidget::class],
            ],
            default => [
                \App\Filament\Widgets\FleetOverview::class,
                [
                    \App\Filament\Widgets\PlanUsageOverview::class,
                    \App\Filament\Widgets\DriverLicenseAlert::class,
                    \App\Filament\Widgets\DriverSubmissionHealth::class,
                ],
                [\App\Filament\Widgets\TruckUtilizationWidget::class],
                // DailyInsights summarizes Truck/Trip/FuelLog data — Fleet-only,
                // so it's scoped to this branch instead of shown unconditionally
                // (Quarry companies reach this same generic dashboard and were
                // previously shown a Fleet AI card that meant nothing to them).
                // AiInsightsWidget is industry-aware (AiInsight::serviceForIndustry())
                // so it's shared across every branch instead of duplicated per industry.
                [\App\Filament\Widgets\PredictiveMaintenanceRisks::class, \App\Filament\Widgets\DailyInsights::class, \App\Filament\Clusters\BusinessIntelligence\Widgets\AiInsightsWidget::class],
                [\App\Filament\Widgets\LatestTrips::class],
            ],
        };

        return [
            // Industry overview cards open the dashboard
            $industryLeadStat,
            // Financial KPI stat cards, all grouped together
            \App\Filament\Widgets\WalletOverview::class,
            \App\Filament\Widgets\ProfitLossOverview::class,
            \App\Filament\Widgets\PendingApprovalsWidget::class,
            ...$industryStats,
            // Charts
            \App\Filament\Widgets\RevenueChart::class,
            \App\Filament\Widgets\ExpenseChart::class,
            ...$industryCharts,
            // AI / insights panels — industry-scoped, see $industryOther above
            ...$industryOther,
            // Tables
            ...$industryTables,
            \App\Filament\Widgets\TopClientsWidget::class,
            \App\Filament\Widgets\OverdueInvoicesWidget::class,
        ];
    }
}
