<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Widgets;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Procurement & Supply Chain Dashboard';

    protected static string $view = 'filament.clusters.procurement.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z" />',
            'title' => 'Procurement & Supply Chain',
            'subtitle' => 'Source-to-pay — planning, requisitions, sourcing, purchase orders, contracts, receiving and spend analytics.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\ProcurementKpiWidget::class,
            Widgets\MonthlyProcurementChart::class,
            Widgets\PurchaseTrendChart::class,
            Widgets\SpendByDepartmentChart::class,
            Widgets\SpendBySupplierChart::class,
            Widgets\SpendByCategoryChart::class,
            Widgets\SupplierPerformanceChart::class,
            Widgets\ContractExpiryChart::class,
            Widgets\BudgetConsumptionChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
