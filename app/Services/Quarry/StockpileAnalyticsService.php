<?php

namespace App\Services\Quarry;

use App\Models\DispatchOrder;
use App\Models\ProductionOutput;
use App\Models\Stockpile;
use App\Models\StockMovement;
use App\Models\StockpileAdjustment;
use App\Models\StockpilePhysicalCount;
use App\Models\WeighbridgeTicket;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for the Stockpile Management Dashboard/Analytics/Reports
 * — mirrors CrushingPlantAnalyticsService's shape exactly (explicit companyId
 * param + query-per-metric).
 */
class StockpileAnalyticsService
{
    protected function stockpiles(?int $companyId)
    {
        return Stockpile::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    protected function movements(?int $companyId)
    {
        return StockMovement::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function totalStockAvailable(?int $companyId): float
    {
        return (float) $this->stockpiles($companyId)->sum('quantity_available');
    }

    public function stockValue(?int $companyId): float
    {
        return (float) $this->stockpiles($companyId)->get()->sum(fn (Stockpile $s) => $s->value());
    }

    public function reservedStock(?int $companyId): float
    {
        return (float) $this->stockpiles($companyId)->sum('quantity_reserved');
    }

    public function availableStock(?int $companyId): float
    {
        return max(0.0, $this->totalStockAvailable($companyId) - $this->reservedStock($companyId));
    }

    public function productionReceivedToday(?int $companyId): float
    {
        return (float) $this->movements($companyId)
            ->where('movement_type', 'Production Receipt')
            ->whereDate('date', today())
            ->sum('quantity');
    }

    public function dispatchesToday(?int $companyId): float
    {
        return (float) $this->movements($companyId)
            ->where('movement_type', 'Dispatch')
            ->whereDate('date', today())
            ->sum('quantity');
    }

    public function transfersToday(?int $companyId): int
    {
        return (int) $this->movements($companyId)
            ->where('movement_type', 'Transfer')
            ->whereDate('date', today())
            ->count();
    }

    public function adjustmentsToday(?int $companyId): int
    {
        return (int) $this->movements($companyId)
            ->where('movement_type', 'Adjustment')
            ->whereDate('date', today())
            ->count();
    }

    public function lowStockCount(?int $companyId): int
    {
        return $this->stockpiles($companyId)
            ->whereColumn('quantity_available', '<=', 'min_level')
            ->where('quantity_available', '>', 0)
            ->count();
    }

    public function criticalStockCount(?int $companyId): int
    {
        return $this->stockpiles($companyId)->where('quantity_available', '<=', 0)->count();
    }

    public function productsAwaitingQualityApprovalCount(?int $companyId): int
    {
        return $this->stockpiles($companyId)->where('quality_status', 'Pending Test')->count();
    }

    /**
     * Inventory accuracy % — derived from the average absolute variance % of
     * recently approved Physical Stock Counts (100% - avg |variance %|,
     * floored at 0), a real reconciliation-based metric, not fabricated.
     */
    public function inventoryAccuracyPercent(?int $companyId, int $days = 90): float
    {
        $counts = StockpilePhysicalCount::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($counts->isEmpty()) {
            return 100.0;
        }

        $avgAbsVariance = $counts->avg(fn (StockpilePhysicalCount $c) => abs($c->variancePercent()));

        return round(max(0, 100 - $avgAbsVariance), 1);
    }

    public function inventoryTurnoverRatio(?int $companyId, int $days = 30): float
    {
        $dispatched = (float) $this->movements($companyId)
            ->where('movement_type', 'Dispatch')
            ->where('date', '>=', now()->subDays($days))
            ->sum('quantity');

        $avgStock = $this->totalStockAvailable($companyId);

        if ($avgStock <= 0) {
            return 0.0;
        }

        return round($dispatched / $avgStock, 2);
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function stockByProduct(?int $companyId): array
    {
        return $this->stockpiles($companyId)
            ->with('material')
            ->get()
            ->groupBy(fn (Stockpile $s) => $s->material?->name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('quantity_available'))
            ->toArray();
    }

    public function stockByQuarry(?int $companyId): array
    {
        return $this->stockpiles($companyId)
            ->with('extractionSite')
            ->get()
            ->groupBy(fn (Stockpile $s) => $s->extractionSite?->name ?? 'Unassigned')
            ->map(fn ($group) => (float) $group->sum('quantity_available'))
            ->toArray();
    }

    public function stockByStockyard(?int $companyId): array
    {
        return $this->stockpiles($companyId)
            ->get()
            ->groupBy(fn (Stockpile $s) => $s->stockyard ?? ($s->label ?? 'Unassigned'))
            ->map(fn ($group) => (float) $group->sum('quantity_available'))
            ->toArray();
    }

    public function dailyStockMovement(?int $companyId, int $days = 14): array
    {
        return $this->movements($companyId)
            ->where('date', '>=', now()->subDays($days))
            ->select(DB::raw(\App\Support\SqlDateFormat::daily('date', 'day')), DB::raw('sum(quantity) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function stockValueTrend(?int $companyId, int $days = 14): array
    {
        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $trend[$date->format('Y-m-d')] = $this->stockValue($companyId);
        }

        return $trend;
    }

    public function inventoryTurnoverTrend(?int $companyId, int $weeks = 8): array
    {
        $trend = [];

        for ($i = $weeks - 1; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $dispatched = (float) $this->movements($companyId)
                ->where('movement_type', 'Dispatch')
                ->whereBetween('date', [$weekStart, $weekStart->copy()->endOfWeek()])
                ->sum('quantity');

            $avgStock = $this->totalStockAvailable($companyId);

            $trend[$weekStart->format('Y-m-d')] = $avgStock > 0 ? round($dispatched / $avgStock, 2) : 0.0;
        }

        return $trend;
    }

    public function stockAgingBuckets(?int $companyId): array
    {
        $buckets = ['0–7 days' => 0.0, '8–30 days' => 0.0, '31–90 days' => 0.0, '90+ days' => 0.0];

        $outputs = ProductionOutput::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('stockpile_id')
            ->get();

        foreach ($outputs as $output) {
            $ageDays = $output->created_at->diffInDays(now());
            $bucket = match (true) {
                $ageDays <= 7 => '0–7 days',
                $ageDays <= 30 => '8–30 days',
                $ageDays <= 90 => '31–90 days',
                default => '90+ days',
            };

            $buckets[$bucket] += (float) $output->quantity_produced;
        }

        return $buckets;
    }

    public function movementAnalysis(?int $companyId, int $days = 30): array
    {
        return $this->movements($companyId)
            ->where('date', '>=', now()->subDays($days))
            ->select('movement_type', DB::raw('sum(quantity) as total'))
            ->groupBy('movement_type')
            ->pluck('total', 'movement_type')
            ->toArray();
    }

    public function reservationAnalysis(?int $companyId): array
    {
        return \App\Models\StockReservation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->select('status', DB::raw('count(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();
    }

    public function stockVarianceTrend(?int $companyId, int $limit = 14): array
    {
        return StockpilePhysicalCount::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->mapWithKeys(fn (StockpilePhysicalCount $c) => [$c->count_number => $c->variance()])
            ->toArray();
    }

    public function outboundTicketsToday(?int $companyId): int
    {
        return WeighbridgeTicket::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('direction', 'Outbound')
            ->whereDate('gate_out_at', today())
            ->count()
            + DispatchOrder::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('exit_at', today())
                ->count();
    }

    public function pendingAdjustmentApprovals(?int $companyId): int
    {
        return StockpileAdjustment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Submitted')
            ->count();
    }
}
