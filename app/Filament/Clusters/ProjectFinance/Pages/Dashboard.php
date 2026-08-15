<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Filament\Clusters\ProjectFinance\Widgets;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'project_finance.view';
    }

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Project Finance Dashboard';

    protected static string $view = 'filament.clusters.project-finance.pages.dashboard';

    /**
     * Filament renders getHeaderWidgets() BEFORE the page's own view content,
     * so the custom title banner must be returned here, not placed inside the
     * view body, or it ends up stranded below every widget.
     */
    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />',
            'title' => 'Project Finance Dashboard',
            'subtitle' => 'Executive view of budgets, costs, revenue and profitability across every project',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\FinanceQuickActionsWidget::class,
            Widgets\FinanceKpiWidget::class,
            Widgets\BudgetVsActualChart::class,
            Widgets\RevenueTrendChart::class,
            Widgets\CostBreakdownChart::class,
            Widgets\CashFlowChart::class,
            Widgets\ProfitTrendChart::class,
            Widgets\ProjectProfitabilityChart::class,
            Widgets\LabourCostChart::class,
            Widgets\EquipmentCostChart::class,
            Widgets\MaterialCostChart::class,
            Widgets\SubcontractorCostChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
