<?php

namespace App\Services\Quarry;

use App\Models\DispatchOrder;
use App\Models\Truck;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for the Dispatch Management Dashboard/Reports —
 * mirrors WeighbridgeAnalyticsService's shape exactly (explicit companyId
 * param + query-per-metric), even though several underlying queries overlap
 * with that service, matching this session's one-service-per-cluster
 * convention.
 */
class DispatchAnalyticsService
{
    protected function orders(?int $companyId)
    {
        return DispatchOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function todaysDispatchesCount(?int $companyId): int
    {
        return $this->orders($companyId)->whereDate('created_at', today())->count();
    }

    public function scheduledDispatchesCount(?int $companyId): int
    {
        return $this->orders($companyId)->whereIn('status', ['Draft', 'Planned'])->count();
    }

    public function completedDispatchesTodayCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Delivered')->whereDate('arrived_at', today())->count();
    }

    public function pendingDispatchesCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Pending Approval')->count();
    }

    public function cancelledDispatchesCount(?int $companyId, int $days = 7): int
    {
        return $this->orders($companyId)->where('status', 'Cancelled')->where('updated_at', '>=', now()->subDays($days))->count();
    }

    public function delayedDispatchesCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Delayed')->count();
    }

    public function trucksEnRouteCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'En Route')->count();
    }

    public function vehiclesWaitingCount(?int $companyId): int
    {
        return $this->orders($companyId)->whereIn('status', ['Checked In', 'Tare Weighed', 'Loading Instruction Generated'])->count();
    }

    public function averageDispatchTimeMinutes(?int $companyId, int $days = 7): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('exit_at')
            ->where('exit_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round((float) $orders->avg(fn (DispatchOrder $o) => $o->created_at->diffInMinutes($o->exit_at)), 1);
    }

    public function averageDeliveryTimeMinutes(?int $companyId, int $days = 7): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('exit_at')->whereNotNull('arrived_at')
            ->where('arrived_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round((float) $orders->avg(fn (DispatchOrder $o) => $o->exit_at->diffInMinutes($o->arrived_at)), 1);
    }

    public function averageTurnaroundMinutes(?int $companyId, int $days = 7): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('gate_in_at')->whereNotNull('exit_at')
            ->where('exit_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round((float) $orders->avg(fn (DispatchOrder $o) => $o->turnaroundMinutes()), 1);
    }

    public function todaysTonnesDelivered(?int $companyId): float
    {
        return (float) $this->orders($companyId)->where('status', 'Delivered')->whereDate('arrived_at', today())->sum('net_weight');
    }

    public function todaysRevenue(?int $companyId): float
    {
        return (float) $this->orders($companyId)
            ->whereNotNull('invoice_id')
            ->whereDate('created_at', today())
            ->with('invoice')
            ->get()
            ->sum(fn (DispatchOrder $o) => (float) ($o->invoice?->total_amount ?? 0));
    }

    public function onTimeDeliveryPercent(?int $companyId, int $days = 30): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('arrived_at')->whereNotNull('eta')
            ->where('arrived_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 100.0;
        }

        $onTime = $orders->filter(fn (DispatchOrder $o) => $o->arrived_at->lte($o->eta))->count();

        return round(($onTime / $orders->count()) * 100, 1);
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function dispatchTrend(?int $companyId, int $days = 14): array
    {
        return $this->orders($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', created_at) as day"), DB::raw('count(*) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function revenueTrend(?int $companyId, int $days = 14): array
    {
        return $this->orders($companyId)
            ->whereNotNull('invoice_id')
            ->where('created_at', '>=', now()->subDays($days))
            ->with('invoice')
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->created_at->format('Y-m-d'))
            ->map(fn ($group) => (float) $group->sum(fn (DispatchOrder $o) => (float) ($o->invoice?->total_amount ?? 0)))
            ->toArray();
    }

    public function tonnesDeliveredTrend(?int $companyId, int $days = 14): array
    {
        return $this->orders($companyId)
            ->where('status', 'Delivered')
            ->whereNotNull('arrived_at')
            ->where('arrived_at', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', arrived_at) as day"), DB::raw('sum(net_weight) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function topCustomers(?int $companyId, int $days = 30, int $limit = 10): array
    {
        return $this->orders($companyId)
            ->whereNotNull('net_weight')
            ->where('created_at', '>=', now()->subDays($days))
            ->with('customer')
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->customer?->company_name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight'))
            ->sortDesc()
            ->take($limit)
            ->toArray();
    }

    public function topProducts(?int $companyId, int $days = 30, int $limit = 10): array
    {
        return $this->orders($companyId)
            ->whereNotNull('net_weight')
            ->where('created_at', '>=', now()->subDays($days))
            ->with('material')
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->material?->name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight'))
            ->sortDesc()
            ->take($limit)
            ->toArray();
    }

    public function truckUtilization(?int $companyId, int $days = 7): array
    {
        return Truck::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->mapWithKeys(fn (Truck $truck) => [
                $truck->plate_number => DispatchOrder::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->where('vehicle_registration', $truck->plate_number)
                    ->where('created_at', '>=', now()->subDays($days))
                    ->count(),
            ])
            ->filter(fn ($count) => $count > 0)
            ->toArray();
    }

    public function deliveryPerformance(?int $companyId, int $days = 30): array
    {
        return [
            'On Time' => $this->orders($companyId)
                ->whereNotNull('arrived_at')->whereNotNull('eta')
                ->where('arrived_at', '>=', now()->subDays($days))
                ->whereColumn('arrived_at', '<=', 'eta')
                ->count(),
            'Late' => $this->orders($companyId)
                ->whereNotNull('arrived_at')->whereNotNull('eta')
                ->where('arrived_at', '>=', now()->subDays($days))
                ->whereColumn('arrived_at', '>', 'eta')
                ->count(),
            'Delayed' => $this->orders($companyId)
                ->where('status', 'Delayed')
                ->where('updated_at', '>=', now()->subDays($days))
                ->count(),
        ];
    }

    public function dispatchesByQuarry(?int $companyId, int $days = 30): array
    {
        return $this->orders($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['weighbridge.extractionSite', 'loadingBay.extractionSite'])
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->weighbridge?->extractionSite?->name
                ?? $o->loadingBay?->extractionSite?->name
                ?? 'Unassigned')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }
}
