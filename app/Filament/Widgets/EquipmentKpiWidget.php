<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EquipmentKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $equipment = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $total = (clone $equipment)->count();
        $allocated = (clone $equipment)->where('status', 'In Use')->count();
        $available = (clone $equipment)->where('status', 'Available')->count();
        $maintenance = (clone $equipment)->where('status', 'Under Maintenance')->count();
        $idle = max(0, $total - $allocated - $maintenance);
        $utilization = $total > 0 ? round(($allocated / $total) * 100) : 0;

        $allocations = EquipmentAllocation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today());

        $hoursToday = (clone $allocations)->sum('operating_hours_today');
        $fuelToday = (clone $allocations)->sum('fuel_consumed_today');

        return [
            Stat::make('Total Equipment', (string) $total)->color('info'),
            Stat::make('Allocated Equipment', (string) $allocated)->color('success'),
            Stat::make('Available Equipment', (string) $available)->color('success'),
            Stat::make('Idle Equipment', (string) $idle)->color($idle > 0 ? 'warning' : 'success'),
            Stat::make('Under Maintenance', (string) $maintenance)->color($maintenance > 0 ? 'danger' : 'success'),
            Stat::make('Equipment Utilization', $utilization . '%')->color('info'),
            Stat::make('Operating Hours Today', number_format((float) $hoursToday, 1))->color('info'),
            Stat::make('Fuel Consumed Today', number_format((float) $fuelToday, 1) . ' L')->color('warning'),
        ];
    }
}
