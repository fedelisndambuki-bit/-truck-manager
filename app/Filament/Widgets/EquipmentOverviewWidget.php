<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\Project;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class EquipmentOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id;

        $totalEquipment = Equipment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->count();

        $underMaintenance = Equipment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Under Maintenance')
            ->count();

        $activeProjects = Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->count();

        return [
            Stat::make('Total Equipment', (string) $totalEquipment)
                ->description('Across all sites')
                ->descriptionIcon('heroicon-m-wrench-screwdriver')
                ->color('info'),
            Stat::make('Under Maintenance', (string) $underMaintenance)
                ->description('Currently unavailable')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($underMaintenance > 0 ? 'warning' : 'success'),
            Stat::make('Active Projects', (string) $activeProjects)
                ->description('In progress')
                ->descriptionIcon('heroicon-m-building-office-2')
                ->color('success'),
        ];
    }
}
