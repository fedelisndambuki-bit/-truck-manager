<?php

namespace App\Filament\Widgets\Quarry;

use App\Services\Quarry\QuarryAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuarrySafetyKpiWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $qa = app(QuarryAnalyticsService::class);

        $daysWithoutLti = $qa->daysWithoutLostTimeInjury($companyId);
        $openIncidents = $qa->openSafetyIncidents($companyId);

        return [
            Stat::make('Days Without LTI', $daysWithoutLti === null ? 'No LTI Recorded' : (string) $daysWithoutLti)
                ->descriptionIcon('heroicon-m-shield-check')
                ->color($daysWithoutLti === null || $daysWithoutLti > 30 ? 'success' : 'warning'),
            Stat::make('Open Safety Incidents', (string) $openIncidents)
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($openIncidents > 0 ? 'danger' : 'success'),
        ];
    }
}
