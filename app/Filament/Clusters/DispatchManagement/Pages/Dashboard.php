<?php

namespace App\Filament\Clusters\DispatchManagement\Pages;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Widgets;
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
        return 'dispatch.view';
    }

    protected static ?string $cluster = DispatchManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Dispatch Management Dashboard';

    protected static string $view = 'filament.clusters.dispatch-management.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 6.75V15m6-6v8.25m.503 3.498l4.875-2.437c.381-.19.622-.58.622-1.006V4.82c0-.836-.88-1.38-1.628-1.006l-3.869 1.934c-.317.159-.69.159-1.006 0L9.503 3.252a1.125 1.125 0 00-1.006 0L3.622 5.689C3.24 5.88 3 6.27 3 6.695V19.18c0 .836.88 1.38 1.628 1.006l3.869-1.934c.317-.159.69-.159 1.006 0l4.994 2.497c.317.158.69.158 1.006 0z" />',
            'title' => 'Dispatch Management',
            'subtitle' => 'Customer order to delivery — planning, scheduling, truck/driver assignment, weighbridge coordination, GPS tracking, proof of delivery, and performance analytics.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\DispatchQuickActionsWidget::class,
            Widgets\DispatchKpiWidget::class,
            Widgets\DispatchTrendChart::class,
            Widgets\RevenueTrendChart::class,
            Widgets\TonnesDeliveredChart::class,
            Widgets\TopCustomersChart::class,
            Widgets\TopProductsChart::class,
            Widgets\TruckUtilizationChart::class,
            Widgets\DeliveryPerformanceChart::class,
            Widgets\DispatchByQuarryChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
