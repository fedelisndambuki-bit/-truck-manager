<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\AiUsageLog;
use App\Models\Company;
use App\Models\Employee;
use App\Models\SubscriptionPayment;
use App\Models\Truck;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlatformOverviewStats extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $todayAi = AiUsageLog::whereDate('created_at', now()->toDateString())->count();
        $monthlyRevenue = SubscriptionPayment::where('status', 'paid')
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        return [
            Stat::make('Total Companies', (string) Company::count()),
            Stat::make('Active Companies', (string) Company::where('status', 'active')->count()),
            Stat::make('Trial Companies', (string) Company::whereNotNull('trial_ends_at')->where('trial_ends_at', '>=', now())->count()),
            Stat::make('Suspended Companies', (string) Company::where('status', 'suspended')->count()),
            Stat::make('Monthly Revenue', 'KES ' . number_format((float) $monthlyRevenue, 2)),
            Stat::make('AI Requests Today', (string) $todayAi),
            Stat::make('Total Trucks', (string) Truck::count()),
            Stat::make('Total Drivers', (string) Employee::count()),
        ];
    }
}
