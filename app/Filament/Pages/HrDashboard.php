<?php

namespace App\Filament\Pages;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;

class HrDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction', 'quarry'];
    }

    protected static string $routePath = 'hr-dashboard';

    protected static string $view = 'filament.pages.hr-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Labour & HR Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Labour & HR Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\HrDashboardKpiWidget::class,
            \App\Filament\Widgets\HrHeadcountByDepartmentChart::class,
            \App\Filament\Widgets\HrHeadcountByEmploymentTypeChart::class,
            \App\Filament\Widgets\HrAttendanceTrendChart::class,
            \App\Filament\Widgets\HrLeaveByTypeChart::class,
            \App\Filament\Widgets\HrPayrollCostTrendChart::class,
            \App\Filament\Widgets\HrTrainingComplianceChart::class,
            \App\Filament\Widgets\HrSafetyIncidentsBySeverityChart::class,
            \App\Filament\Widgets\HrRecruitmentPipelineChart::class,
            \App\Filament\Widgets\HrPpeIssuanceByItemChart::class,
            \App\Filament\Widgets\HrPerformanceRatingChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
