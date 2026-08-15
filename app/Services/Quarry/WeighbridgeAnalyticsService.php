<?php

namespace App\Services\Quarry;

use App\Models\DispatchOrder;
use App\Models\LoadingInstruction;
use App\Models\Weighbridge;
use App\Models\WeighbridgeException;
use App\Models\WeighbridgeTicket;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for the Smart Weighbridge Dashboard/Analytics/Reports
 * — mirrors StockpileAnalyticsService's shape exactly (explicit companyId
 * param + query-per-metric).
 *
 * DispatchOrder (this service's primary model) and WeighbridgeTicket (the
 * real IoT hardware feed, see RecordWeighbridgeReading) are two unreconciled
 * live write paths for the same real-world truck dispatches. Methods below
 * that can be meaningfully combined pull in WeighbridgeTicket too; a few
 * (pendingApprovalsCount, weightAccuracyPercent) have no WeighbridgeTicket
 * equivalent — flagged inline rather than silently unioned.
 */
class WeighbridgeAnalyticsService
{
    protected function orders(?int $companyId)
    {
        return DispatchOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    protected function tickets(?int $companyId)
    {
        return WeighbridgeTicket::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function vehiclesWaitingCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Checked In')->count()
            + $this->tickets($companyId)->where('status', 'Pending')->count();
    }

    public function vehiclesCheckedInTodayCount(?int $companyId): int
    {
        return $this->orders($companyId)->whereDate('gate_in_at', today())->count()
            + $this->tickets($companyId)->whereDate('gate_in_at', today())->count();
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
        return $this->orders($companyId)->where('status', 'Exited')->whereDate('exit_at', today())->count()
            + $this->tickets($companyId)->where('status', 'Weighed')->where('direction', 'Outbound')->whereDate('created_at', today())->count();
    }

    public function todaysTonnes(?int $companyId): float
    {
        // WeighbridgeTicket.net_weight is kg-denominated (see DailyProduction's
        // matching /1000 conversion); DispatchOrder.net_weight is already
        // tonnes-consistent, so only the ticket term needs the conversion.
        return (float) $this->orders($companyId)->whereDate('gross_weighed_at', today())->sum('net_weight')
            + ((float) $this->tickets($companyId)->where('direction', 'Outbound')->where('status', 'Weighed')->whereDate('created_at', today())->sum('net_weight') / 1000);
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

        $tickets = $this->tickets($companyId)
            ->whereNotNull('gate_in_at')->whereNotNull('gate_out_at')
            ->where('gate_out_at', '>=', now()->subDays($days))
            ->get();

        $minutes = $orders->map(fn (DispatchOrder $o) => $o->turnaroundMinutes())
            ->concat($tickets->map(fn (WeighbridgeTicket $t) => $t->turnaroundMinutes()))
            ->filter(fn ($m) => $m !== null);

        if ($minutes->isEmpty()) {
            return 0.0;
        }

        return round($minutes->avg(), 1);
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
        return $this->orders($companyId)->where('status', 'Rejected')->where('updated_at', '>=', now()->subDays($days))->count()
            + $this->tickets($companyId)->where('status', 'Rejected')->where('updated_at', '>=', now()->subDays($days))->count();
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
        $orderData = $this->orders($companyId)
            ->whereDate('exit_at', today())
            ->select(DB::raw(\App\Support\SqlDateFormat::hourly('exit_at', 'hour')), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')->toArray();

        $ticketData = $this->tickets($companyId)
            ->where('direction', 'Outbound')
            ->whereDate('gate_out_at', today())
            ->select(DB::raw(\App\Support\SqlDateFormat::hourly('gate_out_at', 'hour')), DB::raw('count(*) as total'))
            ->groupBy('hour')
            ->pluck('total', 'hour')->toArray();

        $merged = [];
        foreach ([$orderData, $ticketData] as $source) {
            foreach ($source as $hour => $total) {
                $merged[$hour] = ($merged[$hour] ?? 0) + (int) $total;
            }
        }
        ksort($merged);

        return $merged;
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

            $tickets = $this->tickets($companyId)
                ->whereNotNull('gate_in_at')->whereNotNull('gate_out_at')
                ->whereDate('gate_out_at', $date)
                ->get();

            $minutes = $orders->map(fn (DispatchOrder $o) => $o->turnaroundMinutes())
                ->concat($tickets->map(fn (WeighbridgeTicket $t) => $t->turnaroundMinutes()))
                ->filter(fn ($m) => $m !== null);

            $trend[$date->format('Y-m-d')] = $minutes->isEmpty() ? 0 : round($minutes->avg(), 1);
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
        $orderData = $this->orders($companyId)
            ->whereNotNull('gross_weighed_at')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->select(DB::raw(\App\Support\SqlDateFormat::daily('gross_weighed_at', 'day')), DB::raw('sum(net_weight) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')->toArray();

        // WeighbridgeTicket.net_weight is kg-denominated (see DailyProduction's
        // matching /1000 conversion); DispatchOrder.net_weight is tonnes.
        $ticketData = $this->tickets($companyId)
            ->where('direction', 'Outbound')->where('status', 'Weighed')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw(\App\Support\SqlDateFormat::daily('created_at', 'day')), DB::raw('sum(net_weight) as total'))
            ->groupBy('day')
            ->pluck('total', 'day')->toArray();

        $merged = [];
        foreach ($orderData as $day => $total) {
            $merged[$day] = ($merged[$day] ?? 0) + (float) $total;
        }
        foreach ($ticketData as $day => $total) {
            $merged[$day] = ($merged[$day] ?? 0) + ((float) $total / 1000);
        }
        ksort($merged);

        return $merged;
    }

    public function productsDispatched(?int $companyId, int $days = 30): array
    {
        $orderTotals = $this->orders($companyId)
            ->whereNotNull('gross_weighed_at')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->with('material')
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->material?->name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight'));

        $ticketTotals = $this->tickets($companyId)
            ->where('direction', 'Outbound')->where('status', 'Weighed')
            ->where('created_at', '>=', now()->subDays($days))
            ->with('material')
            ->get()
            ->groupBy(fn (WeighbridgeTicket $t) => $t->material?->name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight') / 1000);

        $merged = [];
        foreach ([$orderTotals, $ticketTotals] as $totals) {
            foreach ($totals as $name => $total) {
                $merged[$name] = ($merged[$name] ?? 0) + $total;
            }
        }

        return $merged;
    }

    public function customerDispatches(?int $companyId, int $days = 30): array
    {
        $orderTotals = $this->orders($companyId)
            ->whereNotNull('gross_weighed_at')
            ->where('gross_weighed_at', '>=', now()->subDays($days))
            ->with('customer')
            ->get()
            ->groupBy(fn (DispatchOrder $o) => $o->customer?->company_name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight'));

        $ticketTotals = $this->tickets($companyId)
            ->where('direction', 'Outbound')->where('status', 'Weighed')
            ->where('created_at', '>=', now()->subDays($days))
            ->with('customer')
            ->get()
            ->groupBy(fn (WeighbridgeTicket $t) => $t->customer?->company_name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('net_weight') / 1000);

        $merged = [];
        foreach ([$orderTotals, $ticketTotals] as $totals) {
            foreach ($totals as $name => $total) {
                $merged[$name] = ($merged[$name] ?? 0) + $total;
            }
        }

        return $merged;
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
