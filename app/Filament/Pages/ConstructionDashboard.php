<?php

namespace App\Filament\Pages;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;

class ConstructionDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static string $routePath = 'construction-dashboard';

    protected static string $view = 'filament.pages.construction-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Construction Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\QuickActionsWidget::class,
            \App\Filament\Widgets\ConstructionKpiWidget::class,
            \App\Filament\Widgets\ProjectProgressWidget::class,
            \App\Filament\Widgets\SiteActivityWidget::class,
            \App\Filament\Widgets\EquipmentSummaryWidget::class,
            \App\Filament\Widgets\TopEquipmentWidget::class,
            \App\Filament\Widgets\LabourSummaryWidget::class,
            \App\Filament\Widgets\MaterialSummaryWidget::class,
            \App\Filament\Widgets\TopMaterialsWidget::class,
            \App\Filament\Widgets\ConstructionFinanceWidget::class,
            \App\Filament\Widgets\ProjectMapWidget::class,
            \App\Filament\Widgets\UpcomingTasksWidget::class,
            \App\Filament\Widgets\RecentDocumentsWidget::class,
            \App\Filament\Widgets\MonthlyProjectProgressChart::class,
            \App\Filament\Widgets\BudgetUtilizationChart::class,
            \App\Filament\Widgets\EquipmentUtilizationChart::class,
            \App\Filament\Widgets\LabourProductivityChart::class,
            \App\Filament\Widgets\MaterialConsumptionChart::class,
            \App\Filament\Widgets\ConstructionRevenueTrendChart::class,
            \App\Filament\Widgets\SafetyIncidentsChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
