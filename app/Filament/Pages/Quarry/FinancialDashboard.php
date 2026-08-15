<?php

namespace App\Filament\Pages\Quarry;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class FinancialDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static string $routePath = 'quarry-financial-dashboard';

    protected static string $view = 'filament.pages.quarry.financial-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Financial Dashboard';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 6;

    protected static ?string $title = 'Quarry Financial Dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />',
            'title' => 'Financial Dashboard',
            'subtitle' => 'Revenue, outstanding balances and cost rollups for quarry operations.',
        ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Quarry\QuarryFinanceWidget::class,
            \App\Filament\Widgets\Quarry\RevenueTrendChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
