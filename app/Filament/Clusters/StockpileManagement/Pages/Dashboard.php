<?php

namespace App\Filament\Clusters\StockpileManagement\Pages;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Widgets;
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
        return 'stockpile.view';
    }

    protected static ?string $cluster = StockpileManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Stockpile Management Dashboard';

    protected static string $view = 'filament.clusters.stockpile-management.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />',
            'title' => 'Stockpile Management',
            'subtitle' => 'Real-time inventory visibility — stockpiles, movements, transfers, reservations, adjustments, physical counts and quality status across every quarry, plant, stockyard and depot.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\StockpileQuickActionsWidget::class,
            Widgets\StockpileKpiWidget::class,
            Widgets\StockByProductChart::class,
            Widgets\StockByQuarryChart::class,
            Widgets\StockByStockyardChart::class,
            Widgets\DailyStockMovementChart::class,
            Widgets\StockValueTrendChart::class,
            Widgets\InventoryTurnoverChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
