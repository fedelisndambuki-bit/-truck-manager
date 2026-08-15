<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Labourer;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class LabourSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 6;

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $present = Attendance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->where('status', 'Present')
            ->count();

        $absent = Attendance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->where('status', 'Absent')
            ->count();

        $labourers = Labourer::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $contractors = (clone $labourers)->where('role_on_site', 'Contractor')->count();
        $engineers = (clone $labourers)->where('role_on_site', 'Engineer')->count();
        $foremen = (clone $labourers)->where('role_on_site', 'Foreman')->count();

        $totalToday = $present + $absent;
        $productivity = $totalToday > 0 ? round(($present / $totalToday) * 100) : 0;

        return [
            Stat::make('Workers Present', (string) $present)->color('success'),
            Stat::make('Absent', (string) $absent)->color($absent > 0 ? 'danger' : 'success'),
            Stat::make('Contractors', (string) $contractors)->color('gray'),
            Stat::make('Engineers', (string) $engineers)->color('info'),
            Stat::make('Foremen', (string) $foremen)->color('warning'),
            Stat::make('Productivity', $productivity . '%')->color($productivity >= 80 ? 'success' : 'warning'),
        ];
    }
}
