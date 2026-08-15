<?php

namespace App\Services\Quarry;

use App\Models\DispatchOrder;
use App\Models\LoadingInstruction;
use App\Models\Weighbridge;
use App\Models\WeighbridgeException;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for the Smart Weighbridge Dashboard/Analytics/Reports
 * — mirrors StockpileAnalyticsService's shape exactly (explicit companyId
 * param + query-per-metric).
 */
class WeighbridgeAnalyticsService
{
    protected function orders(?int $companyId)
    {
        return DispatchOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function vehiclesWaitingCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Checked In')->count();
    }

    public function vehiclesCheckedInTodayCount(?int $companyId): int
    {
        return $this->orders($companyId)->whereDate('gate_in_at', today())->count();
    }

    public function vehiclesBeingLoadedCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Loading')->count();
    }

    public function vehiclesAwaitingGrossWeightCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Awaiting Gross Weight')->count();
    }

    public function vehiclesReadyForExitCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Approved')->count();
    }

    public function completedDispatchesTodayCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Exited')->whereDate('exit_at', today())->count();
    }

    public function todaysTonnes(?int $companyId): float
    {
        return (float) $this->orders($companyId)->whereDate('gross_weighed_at', today())->sum('net_weight');
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

    public function averageTruckTurnaroundMinutes(?int $companyId, int $days = 7): float
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

    public function averageWaitingTimeMinutes(?int $companyId, int $days = 7): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('gate_in_at')->whereNotNull('tare_weighed_at')
            ->where('tare_weighed_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round((float) $orders->avg(fn (DispatchOrder $o) => $o->gate_in_at->diffInMinutes($o->tare_weighed_at)), 1);
    }

    public function averageLoadingTimeMinutes(?int $companyId, int $days = 7): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('loading_started_at')->whereNotNull('loading_completed_at')
            ->where('loading_completed_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round((float) $orders->avg(fn (DispatchOrder $o) => $o->loadingDurationMinutes()), 1);
    }

    public function averageWeighingTimeMinutes(?int $companyId, int $days = 7): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('loading_completed_at')->whereNotNull('gross_weighed_at')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round((float) $orders->avg(fn (DispatchOrder $o) => $o->loading_completed_at->diffInMinutes($o->gross_weighed_at)), 1);
    }

    public function currentQueueLength(?int $companyId): int
    {
        return $this->orders($companyId)->whereNotIn('status', ['Approved', 'Exited', 'Rejected', 'Cancelled'])->count();
    }

    public function weightAccuracyPercent(?int $companyId, int $days = 30): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('variance_percent')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->get();

        if ($orders->isEmpty()) {
            return 100.0;
        }

        return round(max(0, 100 - $orders->avg(fn (DispatchOrder $o) => abs((float) $o->variance_percent))), 1);
    }

    public function rejectedLoadsCount(?int $companyId, int $days = 7): int
    {
        return $this->orders($companyId)->where('status', 'Rejected')->where('updated_at', '>=', now()->subDays($days))->count();
    }

    public function loaderProductivityToday(?int $companyId): int
    {
        return LoadingInstruction::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Completed')
            ->whereDate('loading_completed_at', today())
            ->count();
    }

    public function weighbridgeUtilizationPercent(?int $companyId): float
    {
        $weighbridges = Weighbridge::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Online')->get();

        if ($weighbridges->isEmpty()) {
            return 0.0;
        }

        $busy = $weighbridges->filter(fn (Weighbridge $w) => $w->currentQueueCount() > 0)->count();

        return round(($busy / $weighbridges->count()) * 100, 1);
    }

    public function openExceptionsCount(?int $companyId): int
    {
        return WeighbridgeException::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Draft', 'Submitted'])
            ->count();
    }

    public function pendingApprovalsCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Pending Approval')->count();
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function hourlyDispatchesToday(?int $companyId): array
    {
        $rows = $this->orders($companyId)
            ->whereDate('exit_at', today())
            ->select(DB::raw("strftime('%H:00', exit_at) as hour"), DB::raw('count(*) as total'))
            ->groupBy('hour')->orderBy('hour')
            ->pluck('total', 'hour')->toArray();

        return $rows;
    }

    public function truckTurnaroundTrend(?int $companyId, int $days = 14): array
    {
        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $orders = $this->orders($companyId)
                ->whereNotNull('gate_in_at')->whereNotNull('exit_at')
                ->whereDate('exit_at', $date)
                ->get();

            $trend[$date->format('Y-m-d')] = $orders->isEmpty() ? 0 : round((float) $orders->avg(fn (DispatchOrder $o) => $o->turnaroundMinutes()), 1);
        }

        return $trend;
    }

    public function loadingPerformanceTrend(?int $companyId, int $limit = 14): array
    {
        return $this->orders($companyId)
            ->whereNotNull('loading_started_at')->whereNotNull('loading_completed_at')
            ->latest('loading_completed_at')
            ->limit($limit)
            ->get()
            ->sortBy('loading_completed_at')
            ->mapWithKeys(fn (DispatchOrder $o) => [$o->dispatch_number => $o->loadingDurationMinutes()])
            ->toArray();
    }

    public function queueAnalysis(?int $companyId): array
    {
        return $this->orders($companyId)
            ->whereNotIn('status', ['Exited', 'Cancelled'])
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function tonnesDispatchedTrend(?int $companyId, int $days = 14): array
    {
        return $this->orders($companyId)
            ->whereNotNull('gross_weighed_at')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', gross_weighed_at) as day"), DB::raw('sum(net_weight) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function productsDispatched(?int $companyId, int $days = 30): array
    {
        return $this->orders($companyId)
            ->whereNotNull('gross_weighed_at')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->with('material')
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->material?->name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight'))
            ->toArray();
    }

    public function customerDispatches(?int $companyId, int $days = 30): array
    {
        return $this->orders($companyId)
            ->whereNotNull('gross_weighed_at')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->with('customer')
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->customer?->company_name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight'))
            ->toArray();
    }

    public function weighbridgePerformance(?int $companyId): array
    {
        $weighbridges = Weighbridge::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();

        return $weighbridges->mapWithKeys(fn (Weighbridge $w) => [$w->name => $w->averageProcessingTimeMinutes()])->toArray();
    }

    /**
     * "If one weighbridge has a long queue the system should recommend
     * another available weighbridge" — a real queue-length comparison, not
     * a fabricated AI suggestion.
     */
    public function recommendWeighbridge(?int $companyId): ?Weighbridge
    {
        return Weighbridge::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Online')
            ->get()
            ->sortBy(fn (Weighbridge $w) => $w->currentQueueCount())
            ->first();
    }
}
