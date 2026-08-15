<?php

namespace App\Filament\Clusters\ProductionManagement\Pages;

use App\Filament\Clusters\ProductionManagement;
use App\Filament\Clusters\ProductionManagement\Widgets;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'production.view';
    }

    protected static ?string $cluster = ProductionManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Production Management Dashboard';

    protected static string $view = 'filament.clusters.production-management.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />',
            'title' => 'Production Management',
            'subtitle' => 'Extraction through crushing, product output, planning, targets and downtime — the operational backbone of the Quarry ERP.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\ProductionKpiWidget::class,
            \App\Filament\Widgets\Quarry\ProductionTrendChart::class,
            Widgets\PlantPerformanceChart::class,
            Widgets\YieldAnalysisChart::class,
            Widgets\DowntimeAnalysisChart::class,
            Widgets\ShiftPerformanceChart::class,
            Widgets\ProductMixChart::class,
            Widgets\TargetVsActualChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
