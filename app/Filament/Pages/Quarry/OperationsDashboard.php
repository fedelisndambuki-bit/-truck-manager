<?php

namespace App\Filament\Pages\Quarry;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class OperationsDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static string $routePath = 'quarry-operations-dashboard';

    protected static string $view = 'filament.pages.quarry.operations-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Operations Dashboard';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'Quarry Operations Dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12a7.5 7.5 0 0015 0m-15 0a7.5 7.5 0 1115 0m-15 0H3m16.5 0H21m-1.5 0H12m-8.457 3.077l1.41-.513m14.095-5.13l1.41-.513M5.106 17.785l1.15-.964m11.49-9.642l1.149-.964M7.501 19.795l.75-1.3m7.5-12.99l.75-1.3m-6.063 16.658l.26-1.477m2.605-14.772l.26-1.477m0 17.726l-.26-1.477M10.698 4.614l-.26-1.477M16.5 19.794l-.75-1.299M7.5 4.205L12 12" />',
            'title' => 'Operations Dashboard',
            'subtitle' => 'Production, crushing plant status, stockpiles and dispatch/weighbridge activity.',
        ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Quarry\ProductionTrendChart::class,
            \App\Filament\Widgets\Quarry\CrusherPerformanceChart::class,
            \App\Filament\Widgets\Quarry\CrushingPlantStatusWidget::class,
            \App\Filament\Widgets\Quarry\StockpileLevelsChart::class,
            \App\Filament\Widgets\Quarry\StockpileOverviewWidget::class,
            \App\Filament\Widgets\Quarry\DispatchTrendChart::class,
            \App\Filament\Widgets\Quarry\WeighbridgeQueueWidget::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
