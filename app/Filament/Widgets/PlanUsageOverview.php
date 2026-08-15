<?php

namespace App\Filament\Widgets;

use App\Models\AiLog;
use App\Models\Employee;
use App\Models\Truck;
use App\Services\PlanLimitService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PlanUsageOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();

        if (! $user || $user->hasRole('Super Admin') || ! $user->company) {
            return [];
        }

        $company = $user->company;
        $limits = app(PlanLimitService::class)->getCompanyLimits($company);

        $truckCount = Truck::where('company_id', $company->id)->count();
        $driverCount = Employee::where('company_id', $company->id)->count();
        $aiCount = AiLog::where('company_id', $company->id)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return [
            Stat::make('Trucks (Plan Limit)', $this->formatUsage($truckCount, $limits['truck_limit'])),
            Stat::make('Drivers (Plan Limit)', $this->formatUsage($driverCount, $limits['driver_limit'])),
            Stat::make('AI Requests This Month', $this->formatUsage($aiCount, $limits['ai_limit'])),
        ];
    }

    private function formatUsage(int $used, int $limit): string
    {
        if ($limit <= 0) {
            return $used . ' / Unlimited';
        }

        return $used . ' / ' . $limit;
    }
}
