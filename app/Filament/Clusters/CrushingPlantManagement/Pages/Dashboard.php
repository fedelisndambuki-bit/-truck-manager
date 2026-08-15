<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Pages;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Filament\Clusters\CrushingPlantManagement\Widgets;
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
        return 'plant.view';
    }

    protected static ?string $cluster = CrushingPlantManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Crushing Plant Management Dashboard';

    protected static string $view = 'filament.clusters.crushing-plant-management.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75" />',
            'title' => 'Crushing Plant Management',
            'subtitle' => 'Real-time operational control — production lines, crushers, screens, conveyors, feeders, production runs, downtime, energy and OEE.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\PlantKpiWidget::class,
            Widgets\PlantThroughputChart::class,
            \App\Filament\Widgets\Quarry\ProductionTrendChart::class,
            Widgets\PlantEquipmentUtilizationChart::class,
            Widgets\PlantDowntimeTrendChart::class,
            Widgets\OeeTrendChart::class,
            Widgets\PlantYieldAnalysisChart::class,
            Widgets\PlantEnergyConsumptionChart::class,
            \App\Filament\Widgets\Quarry\FuelConsumptionChart::class,
            \App\Filament\Clusters\ProductionManagement\Widgets\ProductMixChart::class,
            Widgets\PlantTargetVsActualChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
