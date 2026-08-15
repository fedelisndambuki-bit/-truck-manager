<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Pages;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Widgets;
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
        return 'eam.view';
    }

    protected static ?string $cluster = EquipmentAssetManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Equipment & Asset Management Dashboard';

    protected static string $view = 'filament.clusters.equipment-asset-management.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h13.5m-13.5 0L6 15.75m3 3l-3-3m0 0V8.25A2.25 2.25 0 018.25 6h6.879a2.25 2.25 0 011.591.659l4.622 4.622a2.25 2.25 0 01.659 1.591V15.75m-13.5 0h13.5m0 0a1.5 1.5 0 003 0m-3 0a1.5 1.5 0 013 0" />',
            'title' => 'Equipment & Asset Management',
            'subtitle' => 'Complete asset lifecycle — register, assignments, utilization, health, maintenance, workshop, parts, tyres, fuel, costs and depreciation.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\EamKpiWidget::class,
            Widgets\AvailabilityTrendChart::class,
            Widgets\UtilizationTrendChart::class,
            \App\Filament\Widgets\Quarry\FuelConsumptionChart::class,
            Widgets\BreakdownFrequencyChart::class,
            Widgets\WorkshopPerformanceChart::class,
            Widgets\EquipmentAgeAnalysisChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
