<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class EquipmentSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $equipment = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $total = (clone $equipment)->count();
        $working = (clone $equipment)->where('status', 'In Use')->count();
        $idle = (clone $equipment)->where('status', 'Available')->count();
        $maintenance = (clone $equipment)->where('status', 'Under Maintenance')->count();

        $allocations = EquipmentAllocation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today());

        $fuelToday = (clone $allocations)->sum('fuel_consumed_today');
        $hoursToday = (clone $allocations)->sum('operating_hours_today');

        return [
            Stat::make('Total Equipment', (string) $total)->color('info'),
            Stat::make('Working', (string) $working)->color('success'),
            Stat::make('Idle', (string) $idle)->color('gray'),
            Stat::make('Under Maintenance', (string) $maintenance)->color($maintenance > 0 ? 'warning' : 'success'),
            Stat::make('Fuel Consumed Today', number_format((float) $fuelToday, 1) . ' L')->color('warning'),
            Stat::make('Operating Hours Today', number_format((float) $hoursToday, 1))->color('info'),
        ];
    }
}
