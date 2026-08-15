<?php

namespace App\Filament\Clusters\SmartWeighbridge\Pages;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Widgets;
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
        return 'weighbridge.view';
    }

    protected static ?string $cluster = SmartWeighbridge::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Smart Weighbridge Dashboard';

    protected static string $view = 'filament.clusters.smart-weighbridge.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" />',
            'title' => 'Smart Weighbridge',
            'subtitle' => 'The digital command center for truck check-in, weighing, loading, dispatch approval, and exit — real-time queue visibility across every weighbridge and loading bay.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\SmartWeighbridgeQuickActionsWidget::class,
            Widgets\WeighbridgeKpiWidget::class,
            Widgets\HourlyDispatchesChart::class,
            Widgets\TruckTurnaroundTrendChart::class,
            Widgets\LoadingPerformanceChart::class,
            Widgets\QueueAnalysisChart::class,
            Widgets\TonnesDispatchedChart::class,
            Widgets\ProductsDispatchedChart::class,
            Widgets\CustomerDispatchesChart::class,
            Widgets\WeighbridgePerformanceChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
