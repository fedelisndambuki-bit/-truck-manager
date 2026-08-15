<?php

namespace App\Filament\Pages;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;

class MaterialsDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static string $routePath = 'materials-dashboard';

    protected static string $view = 'filament.pages.materials-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Materials Management Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\MaterialsDashboardKpiWidget::class,
            \App\Filament\Widgets\MaterialsConsumptionTrendChart::class,
            \App\Filament\Widgets\MaterialsInventoryValueChart::class,
            \App\Filament\Widgets\MaterialsMonthlyProcurementChart::class,
            \App\Filament\Widgets\MaterialsStockMovementChart::class,
            \App\Filament\Widgets\MaterialsSupplierPerformanceChart::class,
            \App\Filament\Widgets\MaterialsCostTrendChart::class,
            \App\Filament\Widgets\MaterialsTopConsumedChart::class,
            \App\Filament\Widgets\MaterialsTopWastedChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
