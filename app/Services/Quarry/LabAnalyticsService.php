<?php

namespace App\Services\Quarry;

use App\Models\Certificate;
use App\Models\LabEquipment;
use App\Models\LabSample;
use App\Models\LabTest;
use App\Models\NonConformance;
use App\Models\QualityCorrectiveAction;
use App\Models\QualityHold;
use Illuminate\Support\Facades\DB;

/**
 * Orchestration layer for the Laboratory & Quality Control Dashboard/
 * Reports — mirrors WeighbridgeAnalyticsService/DispatchAnalyticsService's
 * shape exactly (explicit companyId param + query-per-metric).
 */
class LabAnalyticsService
{
    protected function samples(?int $companyId)
    {
        return LabSample::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    protected function tests(?int $companyId)
    {
        return LabTest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function samplesReceivedTodayCount(?int $companyId): int
    {
        return $this->samples($companyId)->whereDate('created_at', today())->count();
    }

    public function samplesTestedCount(?int $companyId): int
    {
        return $this->tests($companyId)->where('status', 'Completed')->whereDate('end_time', today())->count();
    }

    public function pendingTestsCount(?int $companyId): int
    {
        return $this->tests($companyId)->whereIn('status', ['Pending', 'In Progress'])->count();
    }

    public function approvedProductsCount(?int $companyId): int
    {
        return $this->samples($companyId)->where('status', 'Approved')->count();
    }

    public function rejectedProductsCount(?int $companyId): int
    {
        return $this->samples($companyId)->where('status', 'Rejected')->count();
    }

    public function productsOnHoldCount(?int $companyId): int
    {
        return QualityHold::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->count();
    }

    public function averageTestTimeMinutes(?int $companyId, int $days = 30): float
    {
        $tests = $this->tests($companyId)
            ->whereNotNull('start_time')->whereNotNull('end_time')
            ->where('end_time', '>=', now()->subDays($days))
            ->get();

        if ($tests->isEmpty()) {
            return 0.0;
        }

        return round((float) $tests->avg(fn (LabTest $t) => $t->start_time->diffInMinutes($t->end_time)), 1);
    }

    public function laboratoryUtilizationPercent(?int $companyId): float
    {
        $equipment = LabEquipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();

        if ($equipment->isEmpty()) {
            return 0.0;
        }

        $busy = $equipment->filter(
            fn (LabEquipment $e) => $e->tests()->whereIn('status', ['Pending', 'In Progress'])->exists()
        )->count();

        return round(($busy / $equipment->count()) * 100, 1);
    }

    public function qualityPassRatePercent(?int $companyId, int $days = 30): float
    {
        $tests = $this->tests($companyId)
            ->where('status', 'Completed')
            ->where('end_time', '>=', now()->subDays($days))
            ->get();

        if ($tests->isEmpty()) {
            return 100.0;
        }

        return round(($tests->where('result', 'Pass')->count() / $tests->count()) * 100, 1);
    }

    public function qualityFailRatePercent(?int $companyId, int $days = 30): float
    {
        return round(100 - $this->qualityPassRatePercent($companyId, $days), 1);
    }

    public function openNonConformancesCount(?int $companyId): int
    {
        return NonConformance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();
    }

    public function correctiveActionsCount(?int $companyId): int
    {
        return QualityCorrectiveAction::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Open', 'In Progress'])
            ->count();
    }

    public function customerComplaintsCount(?int $companyId, int $days = 30): int
    {
        return $this->samples($companyId)
            ->where('sample_type', 'Customer Complaint')
            ->where('created_at', '>=', now()->subDays($days))
            ->count();
    }

    public function certificatesIssuedCount(?int $companyId, int $days = 30): int
    {
        return Certificate::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Issued')
            ->where('issued_at', '>=', now()->subDays($days))
            ->count();
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function passRateTrend(?int $companyId, int $days = 14): array
    {
        $trend = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $tests = $this->tests($companyId)->where('status', 'Completed')->whereDate('end_time', $date)->get();

            $trend[$date->format('Y-m-d')] = $tests->isEmpty() ? null : round(($tests->where('result', 'Pass')->count() / $tests->count()) * 100, 1);
        }

        return $trend;
    }

    public function failedTestsTrend(?int $companyId, int $days = 14): array
    {
        return $this->tests($companyId)
            ->where('result', 'Fail')
            ->where('end_time', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', end_time) as day"), DB::raw('count(*) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function productQualityTrend(?int $companyId, int $days = 14): array
    {
        return $this->samples($companyId)
            ->where('status', 'Approved')
            ->where('updated_at', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', updated_at) as day"), DB::raw('count(*) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }

    public function laboratoryWorkload(?int $companyId, int $days = 7): array
    {
        return $this->tests($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('technician')
            ->get()
            ->groupBy(fn (LabTest $t) => $t->technician?->full_name ?? 'Unassigned')
            ->map(fn ($group) => $group->count())
            ->toArray();
    }

    public function qualityByProduct(?int $companyId, int $days = 30): array
    {
        return $this->samples($companyId)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->where('updated_at', '>=', now()->subDays($days))
            ->with('material')
            ->get()
            ->groupBy(fn (LabSample $s) => $s->material?->name ?? 'Unassigned')
            ->map(fn ($group) => $group->where('status', 'Approved')->count())
            ->toArray();
    }

    public function qualityByQuarry(?int $companyId, int $days = 30): array
    {
        return $this->samples($companyId)
            ->whereIn('status', ['Approved', 'Rejected'])
            ->where('updated_at', '>=', now()->subDays($days))
            ->with('extractionSite')
            ->get()
            ->groupBy(fn (LabSample $s) => $s->extractionSite?->name ?? 'Unassigned')
            ->map(fn ($group) => $group->where('status', 'Approved')->count())
            ->toArray();
    }

    public function customerComplaintsTrend(?int $companyId, int $days = 30): array
    {
        return $this->samples($companyId)
            ->where('sample_type', 'Customer Complaint')
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw("strftime('%Y-%m-%d', created_at) as day"), DB::raw('count(*) as total'))
            ->groupBy('day')->orderBy('day')
            ->pluck('total', 'day')->toArray();
    }
}
