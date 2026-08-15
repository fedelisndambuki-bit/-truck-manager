<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\EquipmentAllocation;
use App\Models\MaterialDelivery;
use App\Models\SafetyIncident;
use App\Models\Site;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SiteKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $sites = Site::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $total = (clone $sites)->count();
        $active = (clone $sites)->where('status', 'Active')->count();
        $completed = (clone $sites)->where('status', 'Completed')->count();
        $suspended = (clone $sites)->where('status', 'Suspended')->count();
        $avgProgress = (clone $sites)->avg('progress_percent');

        $siteIds = (clone $sites)->pluck('id');

        $workersToday = Attendance::query()
            ->whereIn('site_id', $siteIds)
            ->whereDate('date', today())
            ->where('status', 'Present')
            ->count();

        $equipmentAssigned = EquipmentAllocation::query()->whereIn('site_id', $siteIds)->count();

        $deliveriesToday = MaterialDelivery::query()
            ->whereIn('site_id', $siteIds)
            ->whereDate('delivered_at', today())
            ->count();

        $openIncidents = SafetyIncident::query()
            ->whereIn('site_id', $siteIds)
            ->whereIn('status', ['Open', 'Investigating'])
            ->count();

        return [
            Stat::make('Total Sites', (string) $total)->color('info'),
            Stat::make('Active Sites', (string) $active)->color('success'),
            Stat::make('Completed Sites', (string) $completed)->color('info'),
            Stat::make('Suspended Sites', (string) $suspended)->color($suspended > 0 ? 'danger' : 'success'),
            Stat::make('Workers On Site Today', (string) $workersToday)->color('success'),
            Stat::make('Equipment Assigned', (string) $equipmentAssigned)->color('warning'),
            Stat::make("Today's Deliveries", (string) $deliveriesToday)->color('info'),
            Stat::make('Open Safety Incidents', (string) $openIncidents)->color($openIncidents > 0 ? 'danger' : 'success'),
            Stat::make('Overall Site Progress', round($avgProgress ?? 0) . '%')->color('success'),
        ];
    }
}
