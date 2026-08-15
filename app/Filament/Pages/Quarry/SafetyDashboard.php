<?php

namespace App\Filament\Pages\Quarry;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Contracts\View\View;

class SafetyDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    protected static string $routePath = 'quarry-safety-dashboard';

    protected static string $view = 'filament.pages.quarry.safety-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationLabel = 'Safety Dashboard';

    protected static ?string $navigationGroup = 'Quarry';

    protected static ?int $navigationSort = 5;

    protected static ?string $title = 'Quarry Safety Dashboard';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z" />',
            'title' => 'Safety Dashboard',
            'subtitle' => 'Incident tracking and days-without-lost-time-injury for quarry operations.',
        ]);
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\Quarry\QuarrySafetyKpiWidget::class,
            \App\Filament\Widgets\Quarry\SafetyTrendChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
