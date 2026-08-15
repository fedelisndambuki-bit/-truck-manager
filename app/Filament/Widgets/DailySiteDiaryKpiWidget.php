<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\DailySiteDiary;
use App\Models\DiaryDelay;
use App\Models\SafetyIncident;
use App\Models\Site;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class DailySiteDiaryKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $today = now()->toDateString();

        $todayDiaries = DailySiteDiary::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', $today);

        $submittedToday = (clone $todayDiaries)->whereIn('status', ['Submitted', 'Reviewed', 'Approved', 'Locked'])->count();
        $pendingDiaries = (clone $todayDiaries)->whereIn('status', ['Submitted', 'Reviewed'])->count();
        $approvedDiaries = (clone $todayDiaries)->whereIn('status', ['Approved', 'Locked'])->count();

        $activeSites = Site::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->count();

        $weatherAlerts = (clone $todayDiaries)->where('weather_impact', 'Severe')->count();

        $openDelays = DiaryDelay::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Open')
            ->count();

        $openIncidents = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Open', 'Investigating'])
            ->count();

        $productivity = round((clone $todayDiaries)->avg('progress_pct') ?? 0);

        $workforceToday = Attendance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', $today)
            ->where('status', 'Present')
            ->count();

        $equipmentUtilization = round((clone $todayDiaries)->avg('equipment_utilization_pct') ?? 0);

        return [
            Stat::make('Diaries Submitted Today', (string) $submittedToday)->descriptionIcon('heroicon-m-paper-airplane')->color('info'),
            Stat::make('Pending Diaries', (string) $pendingDiaries)->descriptionIcon('heroicon-m-clock')->color($pendingDiaries > 0 ? 'warning' : 'success'),
            Stat::make('Approved Diaries', (string) $approvedDiaries)->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Active Sites', (string) $activeSites)->descriptionIcon('heroicon-m-map-pin')->color('info'),
            Stat::make('Weather Alerts', (string) $weatherAlerts)->descriptionIcon('heroicon-m-cloud')->color($weatherAlerts > 0 ? 'danger' : 'success'),
            Stat::make('Open Delays', (string) $openDelays)->descriptionIcon('heroicon-m-exclamation-triangle')->color($openDelays > 0 ? 'warning' : 'success'),
            Stat::make('Open Incidents', (string) $openIncidents)->descriptionIcon('heroicon-m-shield-exclamation')->color($openIncidents > 0 ? 'danger' : 'success'),
            Stat::make('Daily Productivity', $productivity . '%')->descriptionIcon('heroicon-m-chart-bar')->color('success'),
            Stat::make('Total Workforce Today', (string) $workforceToday)->descriptionIcon('heroicon-m-user-group')->color('info'),
            Stat::make('Equipment Utilization Today', $equipmentUtilization . '%')->descriptionIcon('heroicon-m-wrench-screwdriver')->color('warning'),
        ];
    }
}
