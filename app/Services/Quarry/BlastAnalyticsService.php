<?php

namespace App\Services\Quarry;

use App\Models\BlastCompliance;
use App\Models\BlastCost;
use App\Models\BlastDrillHole;
use App\Models\BlastEnvironmentalReading;
use App\Models\BlastExecution;
use App\Models\BlastPlan;
use App\Models\ExplosiveIssue;
use App\Models\FragmentationAnalysis;
use App\Models\ProductionLog;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for the Blast Management Dashboard/Analytics/Reports —
 * mirrors Services\Quarry\QuarryAnalyticsService's shape exactly (explicit
 * companyId param + query-per-metric).
 */
class BlastAnalyticsService
{
    protected function plans(?int $companyId)
    {
        return BlastPlan::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function scheduledBlastsCount(?int $companyId): int
    {
        return $this->plans($companyId)->whereIn('status', ['Approved', 'Submitted'])->whereNotNull('scheduled_date')->count();
    }

    public function completedBlastsCount(?int $companyId): int
    {
        return $this->plans($companyId)->whereIn('status', ['Executed', 'Closed'])->count();
    }

    public function pendingApprovalsCount(?int $companyId): int
    {
        return $this->plans($companyId)->where('status', 'Submitted')->count();
    }

    protected function executions(?int $companyId)
    {
        return BlastExecution::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    public function blastSuccessRatePercent(?int $companyId): float
    {
        $total = $this->executions($companyId)->whereNotNull('blast_outcome')->count();

        if ($total <= 0) {
            return 0.0;
        }

        $successful = $this->executions($companyId)->where('blast_outcome', 'Successful')->count();

        return round(($successful / $total) * 100, 1);
    }

    public function avgFragmentationSize(?int $companyId): float
    {
        return round((float) FragmentationAnalysis::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->avg('average_fragment_size'), 1);
    }

    public function explosivesUsedTotal(?int $companyId, int $days = 30): float
    {
        return (float) ExplosiveIssue::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('issue_date', '>=', now()->subDays($days))
            ->sum('quantity_used');
    }

    public function drilledHolesCount(?int $companyId, int $days = 30): int
    {
        return BlastDrillHole::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function blastDelaysCount(?int $companyId): int
    {
        return $this->executions($companyId)->where('blast_outcome', 'Delayed')->count();
    }

    public function misfiresCount(?int $companyId): int
    {
        return $this->executions($companyId)->where('blast_outcome', 'Misfire')->count();
    }

    public function safetyIncidentsFromBlasts(?int $companyId): int
    {
        return $this->executions($companyId)->whereNotNull('safety_incident_id')->count();
    }

    public function environmentalAlertsCount(?int $companyId): int
    {
        return BlastEnvironmentalReading::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('compliance_status', 'Exceeded')
            ->count();
    }

    /**
     * Production yielded the same day/site as executed blasts — a defensible
     * real proxy (no direct blast_id linkage on production_logs).
     */
    public function productionYieldFromBlasts(?int $companyId, int $days = 30): float
    {
        $executions = $this->executions($companyId)
            ->whereNotNull('actual_date')
            ->where('actual_date', '>=', now()->subDays($days))
            ->with('blastPlan')
            ->get();

        $total = 0.0;

        foreach ($executions as $execution) {
            $siteId = $execution->blastPlan?->extraction_site_id;

            if (! $siteId) {
                continue;
            }

            $total += (float) ProductionLog::query()
                ->where('extraction_site_id', $siteId)
                ->whereDate('date', $execution->actual_date)
                ->sum('quantity_tonnes');
        }

        return round($total, 1);
    }

    // ── Costs & compliance ───────────────────────────────────────────────────

    public function avgComplianceScore(?int $companyId): float
    {
        $compliances = BlastCompliance::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();

        if ($compliances->isEmpty()) {
            return 0.0;
        }

        return round($compliances->avg(fn (BlastCompliance $c) => $c->scorePercent()), 1);
    }

    public function environmentalPerformancePercent(?int $companyId): float
    {
        $total = BlastEnvironmentalReading::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        if ($total <= 0) {
            return 0.0;
        }

        $compliant = BlastEnvironmentalReading::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('compliance_status', 'Compliant')
            ->count();

        return round(($compliant / $total) * 100, 1);
    }

    public function totalBlastCost(?int $companyId, int $days = 90): float
    {
        $costs = BlastCost::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', now()->subDays($days))
            ->get();

        return round($costs->sum(fn (BlastCost $c) => $c->totalCost()), 2);
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function blastsByMonth(?int $companyId, int $months = 6): array
    {
        return $this->plans($companyId)
            ->where('date_planned', '>=', now()->subMonths($months))
            ->select(DB::raw("strftime('%Y-%m', date_planned) as month"), DB::raw('count(*) as total'))
            ->groupBy('month')->orderBy('month')
            ->pluck('total', 'month')->toArray();
    }

    public function explosiveConsumptionTrend(?int $companyId, int $days = 30): array
    {
        return ExplosiveIssue::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('issue_date', '>=', now()->subDays($days))
            ->whereNotNull('issue_date')
            ->select(DB::raw("strftime('%Y-%m-%d', issue_date) as day"), DB::raw('sum(quantity_used) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function fragmentationTrend(?int $companyId, int $limit = 14): array
    {
        return FragmentationAnalysis::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('average_fragment_size')
            ->with('blastPlan')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->mapWithKeys(fn (FragmentationAnalysis $f) => [($f->blastPlan?->blast_number ?? $f->id) => (float) $f->average_fragment_size])
            ->toArray();
    }

    public function blastPerformanceBreakdown(?int $companyId): array
    {
        return $this->executions($companyId)
            ->whereNotNull('blast_outcome')
            ->get()
            ->groupBy('blast_outcome')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }

    public function productionRecoveryTrend(?int $companyId, int $limit = 10): array
    {
        $executions = $this->executions($companyId)
            ->whereNotNull('actual_date')
            ->with('blastPlan')
            ->latest('actual_date')
            ->limit($limit)
            ->get();

        $result = [];

        foreach ($executions->sortBy('actual_date') as $execution) {
            $siteId = $execution->blastPlan?->extraction_site_id;
            $label = $execution->blastPlan?->blast_number ?? $execution->id;

            $result[$label] = $siteId
                ? (float) ProductionLog::query()->where('extraction_site_id', $siteId)->whereDate('date', $execution->actual_date)->sum('quantity_tonnes')
                : 0.0;
        }

        return $result;
    }

    public function blastCostTrend(?int $companyId, int $limit = 10): array
    {
        return BlastCost::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('blastPlan')
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->sortBy('created_at')
            ->mapWithKeys(fn (BlastCost $c) => [($c->blastPlan?->blast_number ?? $c->id) => $c->totalCost()])
            ->toArray();
    }

    public function drillingProductivity(?int $companyId, int $days = 30): array
    {
        return BlastDrillHole::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', now()->subDays($days))
            ->with('drillRig')
            ->get()
            ->groupBy(fn (BlastDrillHole $h) => $h->drillRig?->name ?? 'Unassigned')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }
}
