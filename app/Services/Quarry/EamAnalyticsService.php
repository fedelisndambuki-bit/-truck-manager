<?php

namespace App\Services\Quarry;

use App\Models\Breakdown;
use App\Models\Equipment;
use App\Models\EquipmentCostEntry;
use App\Models\EquipmentUtilizationLog;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\MaintenanceWorkOrder;
use App\Models\Part;
use App\Models\PreventiveMaintenancePlan;

/**
 * Orchestration layer for the Equipment & Asset Management Dashboard/
 * Reports — mirrors CrushingPlantAnalyticsService/BlastAnalyticsService's
 * shape (explicit companyId param + query-per-metric). Built up phase by
 * phase alongside the tables each metric depends on; methods that need a
 * later-phase table are added when that phase lands rather than stubbed
 * out early with fabricated numbers.
 */
class EamAnalyticsService
{
    protected function equipment(?int $companyId)
    {
        return Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function totalEquipmentCount(?int $companyId): int
    {
        return $this->equipment($companyId)->where('status', '!=', 'Disposed')->count();
    }

    public function runningEquipmentCount(?int $companyId): int
    {
        return $this->equipment($companyId)->whereIn('status', ['Operational', 'Available', 'In Use'])->count();
    }

    public function idleEquipmentCount(?int $companyId): int
    {
        return $this->equipment($companyId)->where('status', 'Idle')->count();
    }

    public function underMaintenanceCount(?int $companyId): int
    {
        return $this->equipment($companyId)->where('status', 'Under Maintenance')->count();
    }

    public function outOfServiceCount(?int $companyId): int
    {
        return $this->equipment($companyId)->where('status', 'Out of Service')->count();
    }

    public function availabilityPercent(?int $companyId): float
    {
        $total = $this->totalEquipmentCount($companyId);

        if ($total <= 0) {
            return 0.0;
        }

        $available = $this->equipment($companyId)
            ->where('status', '!=', 'Disposed')
            ->whereNotIn('status', ['Under Maintenance', 'Out of Service'])
            ->count();

        return round(($available / $total) * 100, 1);
    }

    protected function utilizationLogs(?int $companyId)
    {
        return EquipmentUtilizationLog::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    public function utilizationPercentToday(?int $companyId): float
    {
        $logs = $this->utilizationLogs($companyId)->whereDate('log_date', today())->get();

        if ($logs->isEmpty()) {
            return 0.0;
        }

        return round($logs->avg(fn (EquipmentUtilizationLog $log) => $log->utilization_percent), 1);
    }

    public function averageEquipmentAgeYears(?int $companyId): float
    {
        $years = $this->equipment($companyId)->whereNotNull('year')->pluck('year');

        if ($years->isEmpty()) {
            return 0.0;
        }

        return round((int) date('Y') - $years->avg(), 1);
    }

    protected function workOrders(?int $companyId)
    {
        return MaintenanceWorkOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    public function openWorkOrdersCount(?int $companyId): int
    {
        return $this->workOrders($companyId)->whereNotIn('status', ['Closed'])->count();
    }

    public function workshopJobsCount(?int $companyId): int
    {
        return $this->workOrders($companyId)->whereIn('status', ['In Progress', 'Waiting Parts'])->whereNotNull('workshop_id')->count();
    }

    public function preventiveMaintenanceDueCount(?int $companyId): int
    {
        $calendarDue = PreventiveMaintenancePlan::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->where('trigger_type', 'Calendar')
            ->whereNotNull('next_due_value')
            ->where('next_due_value', '<=', now()->timestamp)
            ->count();

        $engineHoursDue = PreventiveMaintenancePlan::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->where('trigger_type', 'EngineHours')
            ->whereNotNull('next_due_value')
            ->with('equipment')
            ->get()
            ->filter(fn (PreventiveMaintenancePlan $plan) => (float) $plan->equipment?->engine_hours >= (float) $plan->next_due_value)
            ->count();

        return $calendarDue + $engineHoursDue;
    }

    public function overdueMaintenanceCount(?int $companyId): int
    {
        return Maintenance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('next_maintenance_date')
            ->where('next_maintenance_date', '<', today())
            ->where('status', '!=', 'Completed')
            ->count();
    }

    public function downtimeHoursToday(?int $companyId): float
    {
        return round((float) Maintenance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('maintenance_date', today())
            ->sum('downtime_hours'), 1);
    }

    public function maintenanceCostToday(?int $companyId): float
    {
        return round((float) $this->workOrders($companyId)->whereDate('closed_at', today())->sum('total_cost'), 2);
    }

    public function averageRepairTimeHours(?int $companyId): float
    {
        return round((float) $this->workOrders($companyId)->where('status', 'Closed')->whereNotNull('actual_hours')->avg('actual_hours'), 1);
    }

    /**
     * Mean Time Between Failures — average elapsed time between consecutive
     * breakdowns on the same equipment, across the fleet, in hours.
     */
    public function mtbfHours(?int $companyId): float
    {
        $intervals = [];

        Breakdown::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('equipment_id')->orderBy('occurred_at')
            ->get(['equipment_id', 'occurred_at'])
            ->groupBy('equipment_id')
            ->each(function ($breakdowns) use (&$intervals) {
                $breakdowns->values()->each(function ($breakdown, $i) use ($breakdowns, &$intervals) {
                    if ($i === 0) {
                        return;
                    }
                    $intervals[] = $breakdown->occurred_at->diffInHours($breakdowns[$i - 1]->occurred_at);
                });
            });

        return empty($intervals) ? 0.0 : round(array_sum($intervals) / count($intervals), 1);
    }

    /**
     * Mean Time To Repair — average elapsed clock time (not logged labor
     * hours) from a breakdown-triggered work order being created to closed.
     */
    public function mttrHours(?int $companyId): float
    {
        $orders = $this->workOrders($companyId)
            ->where('work_order_type', 'Breakdown')
            ->where('status', 'Closed')
            ->whereNotNull('closed_at')
            ->get(['created_at', 'closed_at']);

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round($orders->avg(fn (MaintenanceWorkOrder $o) => $o->created_at->diffInHours($o->closed_at)), 1);
    }

    /**
     * Simplified OEE proxy: Availability x Utilization — a true OEE needs a
     * separate Quality factor that isn't modeled for individual equipment
     * yet, so this intentionally reads as an approximation.
     */
    public function oeePercent(?int $companyId): float
    {
        $availability = $this->availabilityPercent($companyId) / 100;
        $utilization = $this->utilizationPercentToday($companyId) / 100;

        return round($availability * $utilization * 100, 1);
    }

    public function fuelConsumptionTodayLitres(?int $companyId): float
    {
        return round((float) FuelLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('equipment_id')
            ->whereDate('date', today())
            ->sum('quantity_litres'), 1);
    }

    public function lowStockPartsCount(?int $companyId): int
    {
        return Part::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('reorder_level')
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->count();
    }

    public function costPerHour(?int $companyId, int $days = 30): float
    {
        $totalCost = (float) EquipmentCostEntry::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('incurred_on', '>=', now()->subDays($days))
            ->sum('amount');

        $totalHours = (float) $this->utilizationLogs($companyId)
            ->where('log_date', '>=', now()->subDays($days))
            ->sum('operating_hours');

        return $totalHours > 0 ? round($totalCost / $totalHours, 2) : 0.0;
    }

    public function costPerTonne(?int $companyId, int $days = 30): float
    {
        $totalCost = (float) EquipmentCostEntry::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('incurred_on', '>=', now()->subDays($days))
            ->sum('amount');

        $totalTonnes = (float) $this->utilizationLogs($companyId)
            ->where('log_date', '>=', now()->subDays($days))
            ->sum('tonnes_moved');

        return $totalTonnes > 0 ? round($totalCost / $totalTonnes, 2) : 0.0;
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function breakdownFrequencyTrend(?int $companyId, int $days = 30): array
    {
        return Breakdown::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('occurred_at', '>=', now()->subDays($days))
            ->get()
            ->groupBy(fn (Breakdown $b) => $b->occurred_at->format('Y-m-d'))
            ->map(fn ($group) => $group->count())
            ->sortKeys()
            ->toArray();
    }

    public function workshopPerformanceByTechnician(?int $companyId): array
    {
        return $this->workOrders($companyId)
            ->where('status', 'Closed')
            ->whereNotNull('technician_id')
            ->with('technician')
            ->get()
            ->groupBy(fn (MaintenanceWorkOrder $o) => $o->technician?->full_name ?? 'Unknown')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }

    public function utilizationTrend(?int $companyId, int $days = 14): array
    {
        return $this->utilizationLogs($companyId)
            ->where('log_date', '>=', now()->subDays($days))
            ->get()
            ->groupBy(fn (EquipmentUtilizationLog $log) => $log->log_date->format('Y-m-d'))
            ->map(fn ($group) => round($group->avg(fn (EquipmentUtilizationLog $log) => $log->utilization_percent), 1))
            ->sortKeys()
            ->toArray();
    }

    public function availabilityTrend(?int $companyId, int $days = 14): array
    {
        return $this->utilizationLogs($companyId)
            ->where('log_date', '>=', now()->subDays($days))
            ->get()
            ->groupBy(fn (EquipmentUtilizationLog $log) => $log->log_date->format('Y-m-d'))
            ->map(fn ($group) => round($group->avg(fn (EquipmentUtilizationLog $log) => $log->availability_percent), 1))
            ->sortKeys()
            ->toArray();
    }

    public function equipmentAgeAnalysis(?int $companyId): array
    {
        $currentYear = (int) date('Y');
        $buckets = ['0-3 yrs' => 0, '4-7 yrs' => 0, '8-12 yrs' => 0, '13-20 yrs' => 0, '20+ yrs' => 0];

        foreach ($this->equipment($companyId)->whereNotNull('year')->pluck('year') as $year) {
            $age = $currentYear - (int) $year;
            $bucket = match (true) {
                $age <= 3 => '0-3 yrs',
                $age <= 7 => '4-7 yrs',
                $age <= 12 => '8-12 yrs',
                $age <= 20 => '13-20 yrs',
                default => '20+ yrs',
            };
            $buckets[$bucket]++;
        }

        return $buckets;
    }
}
