<?php

namespace App\Filament\Pages;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;

class SubcontractorDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static string $routePath = 'subcontractor-dashboard';

    protected static string $view = 'filament.pages.subcontractor-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Subcontractor Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Subcontractor Management Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\SubcontractorDashboardKpiWidget::class,
            \App\Filament\Widgets\SubcontractorPerformanceChart::class,
            \App\Filament\Widgets\SubcontractorMonthlyPaymentsChart::class,
            \App\Filament\Widgets\SubcontractorContractValueChart::class,
            \App\Filament\Widgets\SubcontractorScopeCompletionChart::class,
            \App\Filament\Widgets\SubcontractorComplianceStatusChart::class,
            \App\Filament\Widgets\SubcontractorProjectsChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
