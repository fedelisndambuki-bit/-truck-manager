<?php

namespace App\Filament\Pages\Quarry;

use App\Filament\Widgets\Quarry as Widgets;
use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ExecutiveDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static string $routePath = 'quarry-dashboard';

    protected static string $view = 'filament.pages.quarry.executive-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Quarry Executive Dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
            'title' => 'Quarry Executive Dashboard',
            'subtitle' => 'Command center for production, plants, stockpiles, dispatch, fleet, commercial and safety operations.',
        ]);
    }

    /**
     * Role-based personalization: each quarry role sees the widget set
     * relevant to its job instead of every card on the command center.
     * Roles not explicitly listed (Super Admin, Company Admin, Fleet
     * Manager, Accountant, Quarry Manager) get the full view.
     */
    public function getWidgets(): array
    {
        $user = Auth::user();

        $full = [
            Widgets\QuarryQuickActionsWidget::class,
            Widgets\QuarryKpiWidget::class,
            Widgets\CrushingPlantStatusWidget::class,
            Widgets\StockpileOverviewWidget::class,
            Widgets\WeighbridgeQueueWidget::class,
            Widgets\QuarrySiteMapWidget::class,
            Widgets\ProductionTrendChart::class,
            Widgets\RevenueTrendChart::class,
            Widgets\DispatchTrendChart::class,
            Widgets\QuarryActivityFeedWidget::class,
        ];

        if (! $user) {
            return $full;
        }

        return match (true) {
            $user->hasRole('Production Manager') => [
                Widgets\QuarryQuickActionsWidget::class,
                Widgets\QuarryKpiWidget::class,
                Widgets\CrushingPlantStatusWidget::class,
                Widgets\ProductionTrendChart::class,
                Widgets\CrusherPerformanceChart::class,
                Widgets\QuarryActivityFeedWidget::class,
            ],
            $user->hasRole('Maintenance Manager') => [
                Widgets\QuarryQuickActionsWidget::class,
                Widgets\QuarryKpiWidget::class,
                Widgets\MaintenanceTrendChart::class,
                Widgets\FuelConsumptionChart::class,
                Widgets\QuarryActivityFeedWidget::class,
            ],
            $user->hasRole('Commercial Manager') => [
                Widgets\QuarryQuickActionsWidget::class,
                Widgets\QuarryKpiWidget::class,
                Widgets\RevenueTrendChart::class,
                Widgets\TopCustomersWidget::class,
                Widgets\DispatchTrendChart::class,
                Widgets\QuarryActivityFeedWidget::class,
            ],
            $user->hasRole('Dispatch Supervisor') => [
                Widgets\QuarryQuickActionsWidget::class,
                Widgets\QuarryKpiWidget::class,
                Widgets\WeighbridgeQueueWidget::class,
                Widgets\DispatchTrendChart::class,
                Widgets\QuarrySiteMapWidget::class,
                Widgets\QuarryActivityFeedWidget::class,
            ],
            $user->hasRole('Weighbridge Operator') => [
                Widgets\QuarryQuickActionsWidget::class,
                Widgets\WeighbridgeQueueWidget::class,
                Widgets\QuarryActivityFeedWidget::class,
            ],
            $user->hasRole('Environmental Officer') => [
                Widgets\QuarryQuickActionsWidget::class,
                Widgets\QuarryKpiWidget::class,
                Widgets\QuarryActivityFeedWidget::class,
            ],
            $user->hasRole('Safety Officer') => [
                Widgets\QuarrySafetyKpiWidget::class,
                Widgets\SafetyTrendChart::class,
                Widgets\QuarryActivityFeedWidget::class,
            ],
            default => $full,
        };
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
