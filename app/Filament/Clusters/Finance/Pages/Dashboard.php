<?php

namespace App\Filament\Clusters\Finance\Pages;

use App\Filament\Clusters\Finance;
use App\Filament\Clusters\Finance\Widgets;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;

class Dashboard extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'finance.general_ledger';
    }

    protected static ?string $cluster = Finance::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?int $navigationSort = 0;

    protected static ?string $title = 'Accounting Dashboard';

    protected static string $view = 'filament.clusters.finance.pages.dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'title' => 'Accounting',
            'subtitle' => 'Executive view of the general ledger — assets, liabilities, revenue, expenses and net income.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\FinanceKpiWidget::class,
            Widgets\RevenueVsExpenseChart::class,
            Widgets\AccountBalancesChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }
}
