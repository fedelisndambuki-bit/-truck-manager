<?php

namespace App\Filament\Clusters\BlastManagement\Pages;

use App\Filament\Clusters\BlastManagement;
use App\Filament\Clusters\BlastManagement\Widgets;
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
        return 'blast.view';
    }

    protected static ?string $cluster = BlastManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Blast Management Dashboard';

    protected static string $view = 'filament.clusters.blast-management.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z" /><path stroke-linecap="round" stroke-linejoin="round" d="M12.75 15a3 3 0 002.978-2.63c.084-.66-.469-1.194-1.134-1.194h-.005c-.376 0-.716.196-.958.474a3.007 3.007 0 01-2.582 1.004c-.665 0-1.218.534-1.134 1.194A3 3 0 0012.75 15z" />',
            'title' => 'Blast Management',
            'subtitle' => 'The full blasting lifecycle — planning, drilling, explosives, safety zones, execution, analysis, environmental monitoring and compliance.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\BlastKpiWidget::class,
            Widgets\BlastsByMonthChart::class,
            Widgets\ExplosiveConsumptionChart::class,
            Widgets\FragmentationTrendChart::class,
            Widgets\BlastPerformanceChart::class,
            Widgets\ProductionRecoveryChart::class,
            Widgets\BlastCostTrendChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
