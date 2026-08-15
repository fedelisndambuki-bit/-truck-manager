<?php

namespace App\Filament\Pages;

use App\Traits\HasIndustryAccess;
use Filament\Pages\Dashboard as BaseDashboard;

class DailySiteDiaryDashboard extends BaseDashboard
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static string $routePath = 'daily-site-diary-dashboard';

    protected static string $view = 'filament.pages.daily-site-diary-dashboard';

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $navigationGroup = 'Construction';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Daily Site Diary Dashboard';

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\DailySiteDiaryKpiWidget::class,
            \App\Filament\Widgets\DiaryDailyProgressChart::class,
            \App\Filament\Widgets\DiaryLabourTrendChart::class,
            \App\Filament\Widgets\DiaryEquipmentUsageChart::class,
            \App\Filament\Widgets\DiaryMaterialConsumptionChart::class,
            \App\Filament\Widgets\DiaryWeatherImpactChart::class,
            \App\Filament\Widgets\DiaryProjectProgressChart::class,
        ];
    }

    public function getColumns(): int|array
    {
        return 2;
    }
}
