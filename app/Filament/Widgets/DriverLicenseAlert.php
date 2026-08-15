<?php

namespace App\Filament\Widgets;

use App\Models\Employee;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DriverLicenseAlert extends BaseWidget
{
    // Refreshes the numbers every 30 seconds
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        // 1. Count licenses expiring in the next 30 days
        $expiringSoon = Employee::whereBetween('license_expiry', [
            now(),
            now()->addDays(30),
        ])->count();

        // 2. Count licenses that have already passed their expiry date
        $alreadyExpired = Employee::where('license_expiry', '<', now())->count();

        return [
            // Stat for Expiring Soon
            Stat::make('Licenses Expiring (30 Days)', $expiringSoon)
                ->description($expiringSoon > 0 ? 'Urgent renewals needed' : 'All drivers are compliant')
                ->descriptionIcon('heroicon-s-identification')
                ->color($expiringSoon > 0 ? 'warning' : 'success'),

            // Stat for Already Expired
            Stat::make('Expired Licenses', $alreadyExpired)
                ->description($alreadyExpired > 0 ? 'Drivers grounded: Immediate action' : 'No expired licenses')
                ->descriptionIcon('heroicon-s-x-circle')
                ->color($alreadyExpired > 0 ? 'danger' : 'success'),

            // Optional: Total Active Drivers
            Stat::make('Total Active Drivers', Employee::where('status', 'Active')->count())
                ->description('Current fleet strength')
                ->descriptionIcon('heroicon-s-users')
                ->color('info'),
        ];
    }
}
