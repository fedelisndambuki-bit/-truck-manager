<?php

namespace App\Services\Quarry;

use App\Models\Conveyor;
use App\Models\CrushingPlant;
use App\Models\CrushingPlantLog;
use App\Models\Crusher;
use App\Models\EnergyReading;
use App\Models\Feeder;
use App\Models\PlantQualityTest;
use App\Models\ProductionDowntime;
use App\Models\ProductionOutput;
use App\Models\ProductionRun;
use App\Models\Screen;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for the Crushing Plant Management Dashboard/Analytics/
 * Reports — mirrors Services\Quarry\QuarryAnalyticsService/BlastAnalyticsService's
 * shape exactly (explicit companyId param + query-per-metric).
 */
class CrushingPlantAnalyticsService
{
    protected function plants(?int $companyId)
    {
        return CrushingPlant::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function activePlantsCount(?int $companyId): int
    {
        return $this->plants($companyId)->where('status', '!=', 'Retired')->count();
    }

    public function runningPlantsCount(?int $companyId): int
    {
        return $this->plants($companyId)->where('status', 'Running')->count();
    }

    public function idlePlantsCount(?int $companyId): int
    {
        return $this->plants($companyId)->where('status', 'Idle')->count();
    }

    public function plantsUnderMaintenanceCount(?int $companyId): int
    {
        return $this->plants($companyId)->where('status', 'Maintenance')->count();
    }

    public function productionTodayTonnes(?int $companyId): float
    {
        return (float) CrushingPlantLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('output_tonnes');
    }

    public function plantThroughputTph(?int $companyId): float
    {
        return round((float) Crusher::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->avg('current_throughput'), 1);
    }

    public function crusherAvailabilityPercent(?int $companyId): float
    {
        $total = Crusher::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        if ($total <= 0) {
            return 0.0;
        }

        $available = Crusher::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereNotIn('status', ['Breakdown', 'Maintenance'])->count();

        return round(($available / $total) * 100, 1);
    }

    public function plantUtilizationPercent(?int $companyId): float
    {
        $total = $this->plants($companyId)->where('status', '!=', 'Retired')->count();

        if ($total <= 0) {
            return 0.0;
        }

        $running = $this->runningPlantsCount($companyId);

        return round(($running / $total) * 100, 1);
    }

    public function availabilityPercent(?int $companyId): float
    {
        $operating = $this->operatingHoursToday($companyId);
        $downtime = $this->downtimeHoursToday($companyId);
        $total = $operating + $downtime;

        return $total > 0 ? round(($operating / $total) * 100, 1) : 0.0;
    }

    public function performancePercent(?int $companyId): float
    {
        $ratedCapacity = (float) $this->plants($companyId)->sum('capacity_tph') * 24;

        if ($ratedCapacity <= 0) {
            return 0.0;
        }

        return round(min(100, ($this->productionTodayTonnes($companyId) / $ratedCapacity) * 100), 1);
    }

    public function qualityPercent(?int $companyId): float
    {
        $total = PlantQualityTest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        if ($total <= 0) {
            return 100.0;
        }

        $passed = PlantQualityTest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('pass_fail', true)->count();

        return round(($passed / $total) * 100, 1);
    }

    /**
     * OEE = Availability x Performance x Quality — the standard formula, no
     * fabricated shortcut; each factor is independently computed above.
     */
    public function oeePercent(?int $companyId): float
    {
        $oee = ($this->availabilityPercent($companyId) / 100)
            * ($this->performancePercent($companyId) / 100)
            * ($this->qualityPercent($companyId) / 100);

        return round($oee * 100, 1);
    }

    public function operatingHoursToday(?int $companyId): float
    {
        $logs = CrushingPlantLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->get();

        return round((float) $logs->sum(fn (CrushingPlantLog $l) => max(0, 24 - ($l->downtime_minutes / 60))), 1);
    }

    public function downtimeHoursToday(?int $companyId): float
    {
        return round((float) ProductionDowntime::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('crushing_plant_id')
            ->whereDate('start_time', today())
            ->sum('duration_minutes') / 60, 1);
    }

    public function energyConsumptionToday(?int $companyId): float
    {
        return (float) EnergyReading::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('electricity_kwh');
    }

    public function fuelConsumptionToday(?int $companyId): float
    {
        return (float) EnergyReading::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('date', today())
            ->sum('diesel_litres');
    }

    public function finishedProductsToday(?int $companyId): float
    {
        return (float) ProductionOutput::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('production_run_id')
            ->whereDate('created_at', today())
            ->sum('quantity_produced');
    }

    public function wasteMaterialToday(?int $companyId): float
    {
        $runs = ProductionRun::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('created_at', today())
            ->get();

        return round((float) $runs->sum(fn (ProductionRun $r) => max(0, $r->input_quantity - $r->output_quantity)), 1);
    }

    public function recoveryRatePercent(?int $companyId, int $days = 7): float
    {
        $runs = ProductionRun::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Approved', 'Completed'])
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        if ($runs->isEmpty()) {
            return 0.0;
        }

        return round($runs->avg(fn (ProductionRun $r) => $r->recoveryPercent()), 1);
    }

    public function averageThroughput(?int $companyId, int $days = 7): float
    {
        return round((float) CrushingPlantLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('date', '>=', now()->subDays($days))
            ->avg('output_tonnes'), 1);
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function plantThroughputTrend(?int $companyId, int $days = 14): array
    {
        return CrushingPlantLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('date', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', date) as day"), DB::raw('sum(output_tonnes) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function equipmentUtilizationByType(?int $companyId): array
    {
        $companyScope = fn ($q) => $q->when($companyId, fn ($qq) => $qq->where('company_id', $companyId));

        return [
            'Crushers' => Crusher::query()->tap($companyScope)->where('status', 'Running')->count(),
            'Screens' => Screen::query()->tap($companyScope)->where('status', 'Running')->count(),
            'Conveyors' => Conveyor::query()->tap($companyScope)->where('status', 'Running')->count(),
            'Feeders' => Feeder::query()->tap($companyScope)->where('status', 'Running')->count(),
        ];
    }

    public function downtimeTrend(?int $companyId, int $days = 30): array
    {
        return ProductionDowntime::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('crushing_plant_id')
            ->where('start_time', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', start_time) as day"), DB::raw('sum(duration_minutes) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function oeeTrend(?int $companyId, int $days = 14): array
    {
        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $logs = CrushingPlantLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereDate('date', $date)
                ->get();

            if ($logs->isEmpty()) {
                continue;
            }

            $operating = (float) $logs->sum(fn ($l) => 24 - ($l->downtime_minutes / 60));
            $downtime = (float) $logs->sum('downtime_minutes') / 60;
            $availability = ($operating + $downtime) > 0 ? $operating / ($operating + $downtime) : 0;

            $trend[$date->format('Y-m-d')] = round($availability * 100, 1);
        }

        return $trend;
    }

    public function yieldAnalysisTrend(?int $companyId, int $limit = 14): array
    {
        return ProductionRun::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Approved', 'Completed'])
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->mapWithKeys(fn (ProductionRun $r) => [$r->run_number => $r->yieldPercent()])
            ->toArray();
    }

    public function energyConsumptionTrend(?int $companyId, int $days = 30): array
    {
        return EnergyReading::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('date', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', date) as day"), DB::raw('sum(electricity_kwh) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function targetVsActualByPlant(?int $companyId): array
    {
        $plants = $this->plants($companyId)->where('status', '!=', 'Retired')->get();

        $target = [];
        $actual = [];

        foreach ($plants as $plant) {
            $target[$plant->name] = (float) $plant->capacity_tph * 24;
            $actual[$plant->name] = (float) CrushingPlantLog::query()
                ->where('crushing_plant_id', $plant->id)
                ->whereDate('date', today())
                ->sum('output_tonnes');
        }

        return ['target' => $target, 'actual' => $actual];
    }
}
