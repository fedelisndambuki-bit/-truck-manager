<?php

namespace App\Filament\SuperAdmin\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $title = 'Tanaki Control Room';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            \App\Filament\SuperAdmin\Widgets\PlatformOverviewStats::class,
            \App\Filament\SuperAdmin\Widgets\PlatformGrowthChart::class,
            \App\Filament\SuperAdmin\Widgets\RecentSystemLogs::class,
        ];
    }
}
