<?php

namespace App\Filament\Widgets;

use App\Models\FuelLog;
use App\Models\Trip;
use App\Models\Truck;
use Carbon\Carbon;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Schema;

class FleetOverview extends BaseWidget
{
    // Optional: Refresh data every 30 seconds
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // 1. Total Trucks (Safety check if table exists)
        $totalTrucks = Schema::hasTable('trucks') ? Truck::count() : 0;

        // 2. Active Trips
        $activeTrips = Schema::hasTable('trips') ? Trip::where('status', 'In Progress')->count() : 0;

        // 3. Maintenance Count
        $underMaintenance = Schema::hasTable('trucks') ? Truck::where('status', 'Under Maintenance')->count() : 0;

        // 4. Monthly Fuel Calculation
        $monthlyFuel = 0;
        if (Schema::hasTable('fuel_logs')) {
            $monthlyFuel = FuelLog::whereMonth('date', Carbon::now()->month)
                ->whereYear('date', Carbon::now()->year)
                ->sum('total_cost');
        }

        return [
            Stat::make('Total Trucks', $totalTrucks)
                ->description('Total fleet size')
                ->descriptionIcon('heroicon-s-truck')
                ->color('info'),

            Stat::make('Active Trips', $activeTrips)
                ->description('Trucks currently on road')
                ->descriptionIcon('heroicon-s-arrow-trending-up')
                ->color('warning'),

            Stat::make('Under Maintenance', $underMaintenance)
                ->description('Currently in workshop')
                ->descriptionIcon('heroicon-s-wrench-screwdriver')
                ->color('danger'),

            Stat::make('Monthly Fuel', 'KES '.number_format((float) $monthlyFuel, 2))
                ->description('Spend for '.Carbon::now()->format('F'))
                ->chart([10, 20, 15, 25, 30, 20, 40])
                ->color('success'),
        ];
    }
}
