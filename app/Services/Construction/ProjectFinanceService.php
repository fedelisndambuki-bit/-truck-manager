<?php

namespace App\Services\Construction;

use App\Models\ChangeOrder;
use App\Models\Commitment;
use App\Models\Contract;
use App\Models\DiaryDelay;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\Labourer;
use App\Models\MaterialConsumption;
use App\Models\Payment;
use App\Models\PaymentCertificate;
use App\Models\Project;
use App\Models\PurchaseOrder;
use App\Models\QualityInspection;
use App\Models\RetentionEntry;
use App\Models\Site;
use App\Models\Variation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Single source of truth for rolling construction project costs/revenue up from
 * every operational module (Labour, Materials, Equipment, Subcontractors,
 * Procurement, Site Expenses) — used by the Dashboard, Cost Control, Forecasting,
 * Profitability and every Report so none of them re-implement this math.
 */
class ProjectFinanceService
{
    public function labourCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $labourers = Labourer::query()->where('project_id', $project->id)->get(['id', 'employee_id', 'daily_rate']);

        if ($labourers->isEmpty()) {
            return 0.0;
        }

        $total = 0.0;

        foreach ($labourers->groupBy('employee_id') as $employeeId => $group) {
            $rate = (float) ($group->first()->daily_rate ?? 0);

            if ($rate <= 0) {
                continue;
            }

            $days = \App\Models\Attendance::query()
                ->where('employee_id', $employeeId)
                ->where('status', 'Present')
                ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
                ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
                ->count();

            $total += $rate * $days;
        }

        return round($total, 2);
    }

    public function materialsCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return round((float) MaterialConsumption::query()
            ->where('project_id', $project->id)
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->sum('cost'), 2);
    }

    public function equipmentCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        // Summed in PHP (not via a SQL join) because the shared MultiTenantScope
        // global scope adds an unqualified `company_id` condition that becomes
        // ambiguous once `equipment` (which also has a `company_id` column) is joined.
        $total = \App\Models\EquipmentAllocation::query()
            ->where('project_id', $project->id)
            ->when($from, fn ($q) => $q->whereDate('allocated_from', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('allocated_to', '<=', $to))
            ->with('equipment:id,hourly_rate')
            ->get()
            ->sum(function ($allocation) use ($from, $to) {
                $hourlyRate = (float) ($allocation->equipment?->hourly_rate ?? 0);
                $dailyHours = (float) $allocation->operating_hours_today;

                if ($hourlyRate <= 0 || $dailyHours <= 0) {
                    return 0.0;
                }

                // operating_hours_today is only ever a same-day snapshot — there's
                // no historical daily log of equipment hours — so it's treated as
                // a representative daily rate and multiplied by the number of
                // days actually allocated (clamped to the requested period, if
                // any), rather than being counted once for the whole allocation
                // regardless of how many weeks it spans.
                $periodStart = $allocation->allocated_from ?: $allocation->created_at;
                $periodEnd = $allocation->allocated_to ?: now();

                if ($from && $periodStart->lt($from)) {
                    $periodStart = $from;
                }
                if ($to && $periodEnd->gt($to)) {
                    $periodEnd = $to;
                }

                $days = max(1, $periodStart->diffInDays($periodEnd) + 1);

                return $dailyHours * $hourlyRate * $days;
            });

        return round($total, 2);
    }

    public function subcontractorCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $certified = (float) PaymentCertificate::query()
            ->where('scope', 'subcontractor')
            ->whereIn('contract_id', Contract::query()->where('project_id', $project->id)->pluck('id'))
            ->where('status', '!=', 'Draft')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('approved_amount');

        $approvedVariations = (float) Variation::query()
            ->whereIn('contract_id', Contract::query()->where('project_id', $project->id)->pluck('id'))
            ->where('status', 'Approved')
            ->sum('cost_impact');

        return round($certified + $approvedVariations, 2);
    }

    public function procurementCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return round((float) PurchaseOrder::query()
            ->where('project_id', $project->id)
            ->where('approval_status', 'Approved')
            ->when($from, fn ($q) => $q->whereDate('created_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('created_at', '<=', $to))
            ->sum('total_amount'), 2);
    }

    public function siteExpenseCost(Project $project, ?string $category = null, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return round((float) Expense::query()
            ->where('project_id', $project->id)
            ->when($category, fn ($q) => $q->where('category', $category))
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->sum('amount'), 2);
    }

    public function changeOrderCost(Project $project): float
    {
        return round((float) ChangeOrder::query()
            ->where('project_id', $project->id)
            ->where('status', 'Approved')
            ->sum('cost_impact'), 2);
    }

    /**
     * Daily Site Diary integration: estimated cost impact of logged site delays
     * (prolongation, idle plant/labour) for this project's diaries.
     */
    public function delayCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return round((float) DiaryDelay::query()
            ->whereHas('diary', function ($q) use ($project, $from, $to) {
                $q->where('project_id', $project->id)
                    ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
                    ->when($to, fn ($q) => $q->whereDate('date', '<=', $to));
            })
            ->sum('cost_impact'), 2);
    }

    /**
     * Quality Control integration: rework/remediation cost recorded against
     * failed inspections on this project's sites.
     */
    public function qualityReworkCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        $siteIds = Site::query()->where('project_id', $project->id)->pluck('id');

        if ($siteIds->isEmpty()) {
            return 0.0;
        }

        return round((float) QualityInspection::query()
            ->whereIn('site_id', $siteIds)
            ->where('result', 'Fail')
            ->when($from, fn ($q) => $q->whereDate('date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('date', '<=', $to))
            ->sum('rework_cost'), 2);
    }

    public function totalActualCost(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return round(
            $this->labourCost($project, $from, $to)
            + $this->materialsCost($project, $from, $to)
            + $this->equipmentCost($project, $from, $to)
            + $this->subcontractorCost($project, $from, $to)
            + $this->procurementCost($project, $from, $to)
            + $this->siteExpenseCost($project, null, $from, $to)
            + $this->delayCost($project, $from, $to)
            + $this->qualityReworkCost($project, $from, $to),
            2
        );
    }

    public function costBreakdown(Project $project, ?Carbon $from = null, ?Carbon $to = null): array
    {
        return [
            'Labour' => $this->labourCost($project, $from, $to),
            'Materials' => $this->materialsCost($project, $from, $to),
            'Equipment' => $this->equipmentCost($project, $from, $to),
            'Subcontractors' => $this->subcontractorCost($project, $from, $to),
            'Procurement' => $this->procurementCost($project, $from, $to),
            'Site Expenses' => $this->siteExpenseCost($project, 'Site Expenses', $from, $to),
            'Utilities' => $this->siteExpenseCost($project, 'Utilities', $from, $to),
            'Transport' => $this->siteExpenseCost($project, 'Transport', $from, $to),
            'Overheads' => $this->siteExpenseCost($project, 'Overheads', $from, $to),
            'Delay Impact' => $this->delayCost($project, $from, $to),
            'Quality Rework' => $this->qualityReworkCost($project, $from, $to),
        ];
    }

    /**
     * Actual cost scoped to a specific budget line (cost code and/or category), used by
     * ProjectBudget::recalculateActual() to keep individual budget lines in sync.
     *
     * Cost codes/categories that name one of this service's own tracked resource
     * buckets (Labour, Materials, Equipment, Subcontractors, Procurement) route to
     * the real calculation for that bucket, not an Expense lookup — Expense rows
     * are only ever categorised as Site Expenses/Utilities/Transport/Overheads,
     * so matching a resource-bucket name against Expense.category would always
     * return zero. Trade/BOQ-level cost codes (Earthworks, Concrete, Steel,
     * Masonry, ...) have no actual-cost tracking dimension anywhere in the data
     * model at all — null signals "nothing to recalculate from" so the caller
     * can leave the existing value alone instead of overwriting it with zero.
     */
    public function costForCostCode(Project $project, ?int $costCodeId, ?string $category): ?float
    {
        $codeName = $costCodeId ? \App\Models\CostCode::find($costCodeId)?->name : null;
        $match = $codeName ?: $category;

        if (! $match) {
            return $this->totalActualCost($project);
        }

        $resourceCostMethod = match ($match) {
            'Labour' => 'labourCost',
            'Materials' => 'materialsCost',
            'Equipment' => 'equipmentCost',
            'Subcontractors' => 'subcontractorCost',
            'Procurement' => 'procurementCost',
            default => null,
        };

        if ($resourceCostMethod) {
            return $this->{$resourceCostMethod}($project);
        }

        if (in_array($match, ['Site Expenses', 'Utilities', 'Transport', 'Overheads'], true)) {
            return round((float) Expense::query()
                ->where('project_id', $project->id)
                ->where('category', $match)
                ->sum('amount'), 2);
        }

        return null;
    }

    public function totalRevenue(Project $project): float
    {
        return round((float) Payment::query()
            ->where('project_id', $project->id)
            ->where('status', 'Paid')
            ->sum('amount'), 2);
    }

    public function outstandingClientAmount(Project $project): float
    {
        return round((float) Invoice::query()
            ->where('project_id', $project->id)
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total_amount'), 2);
    }

    public function outstandingSupplierAmount(Project $project): float
    {
        $commitments = Commitment::query()->where('project_id', $project->id)->get();

        return round((float) $commitments->sum(fn (Commitment $c) => max(0, (float) $c->committed_amount - (float) $c->invoiced_to_date)), 2);
    }

    public function retentionHeld(Project $project): float
    {
        return round((float) RetentionEntry::query()
            ->where('project_id', $project->id)
            ->get()
            ->sum(fn (RetentionEntry $r) => max(0, (float) $r->held_amount - (float) $r->released_amount)), 2);
    }

    // ── Earned Value Management ─────────────────────────────────────────────

    public function budgetAtCompletion(Project $project): float
    {
        $approved = (float) \App\Models\ProjectBudget::query()
            ->where('project_id', $project->id)
            ->where('status', 'Approved')
            ->get()
            ->sum(fn ($b) => (float) ($b->revised_budget > 0 ? $b->revised_budget : $b->approved_budget));

        return $approved > 0 ? $approved : (float) ($project->estimated_cost ?: $project->budget ?: $project->contract_value ?: 0);
    }

    public function percentComplete(Project $project): float
    {
        $boqTotal = (float) $project->boqItems()->sum('amount');

        if ($boqTotal > 0) {
            $completed = (float) $project->boqItems()->get()->sum(fn ($i) => (float) $i->completed_quantity * (float) $i->rate);

            return round(min(100, ($completed / $boqTotal) * 100), 2);
        }

        return (float) ($project->progress_percent ?? 0);
    }

    public function plannedPercentComplete(Project $project): float
    {
        if (! $project->start_date || ! $project->end_date) {
            return $this->percentComplete($project);
        }

        $totalDays = max(1, $project->start_date->diffInDays($project->end_date));
        $elapsed = min($totalDays, max(0, $project->start_date->diffInDays(now())));

        return round(min(100, ($elapsed / $totalDays) * 100), 2);
    }

    public function earnedValue(Project $project): float
    {
        return round($this->budgetAtCompletion($project) * $this->percentComplete($project) / 100, 2);
    }

    public function plannedValue(Project $project): float
    {
        return round($this->budgetAtCompletion($project) * $this->plannedPercentComplete($project) / 100, 2);
    }

    public function costPerformanceIndex(Project $project): float
    {
        $ac = $this->totalActualCost($project);

        return $ac > 0 ? round($this->earnedValue($project) / $ac, 3) : 1.0;
    }

    public function schedulePerformanceIndex(Project $project): float
    {
        $pv = $this->plannedValue($project);

        return $pv > 0 ? round($this->earnedValue($project) / $pv, 3) : 1.0;
    }

    public function costVariance(Project $project): float
    {
        return round($this->earnedValue($project) - $this->totalActualCost($project), 2);
    }

    public function scheduleVariance(Project $project): float
    {
        return round($this->earnedValue($project) - $this->plannedValue($project), 2);
    }

    public function estimateAtCompletion(Project $project): float
    {
        $cpi = $this->costPerformanceIndex($project);

        return $cpi > 0 ? round($this->budgetAtCompletion($project) / $cpi, 2) : $this->budgetAtCompletion($project);
    }

    // ── Cash flow ────────────────────────────────────────────────────────────

    public function cashIn(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return round((float) Payment::query()
            ->where('project_id', $project->id)
            ->where('status', 'Paid')
            ->when($from, fn ($q) => $q->whereDate('payment_date', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('payment_date', '<=', $to))
            ->sum('amount'), 2);
    }

    public function cashOut(Project $project, ?Carbon $from = null, ?Carbon $to = null): float
    {
        return round(
            $this->totalActualCost($project, $from, $to),
            2
        );
    }

    /**
     * Company-wide cost breakdown, summed across every project — backs the
     * Dashboard's Cost Breakdown / Labour / Equipment / Material / Subcontractor
     * Cost widgets so they don't each re-implement the per-project loop.
     */
    public function companyCostBreakdown(?int $companyId): array
    {
        $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();

        $totals = [
            'Labour' => 0.0, 'Materials' => 0.0, 'Equipment' => 0.0,
            'Subcontractors' => 0.0, 'Procurement' => 0.0, 'Overheads' => 0.0,
            'Delay Impact' => 0.0, 'Quality Rework' => 0.0,
        ];

        foreach ($projects as $project) {
            $totals['Labour'] += $this->labourCost($project);
            $totals['Materials'] += $this->materialsCost($project);
            $totals['Equipment'] += $this->equipmentCost($project);
            $totals['Subcontractors'] += $this->subcontractorCost($project);
            $totals['Procurement'] += $this->procurementCost($project);
            $totals['Overheads'] += $this->siteExpenseCost($project, 'Overheads');
            $totals['Delay Impact'] += $this->delayCost($project);
            $totals['Quality Rework'] += $this->qualityReworkCost($project);
        }

        return array_map(fn ($v) => round($v, 2), $totals);
    }

    public function projectedCashFlow(Project $project): float
    {
        $actual = $this->cashIn($project) - $this->cashOut($project);

        $forecastIn = (float) \App\Models\CashFlowForecast::query()
            ->where('project_id', $project->id)
            ->where('entry_type', 'forecast')
            ->where('direction', 'in')
            ->sum('amount');

        $forecastOut = (float) \App\Models\CashFlowForecast::query()
            ->where('project_id', $project->id)
            ->where('entry_type', 'forecast')
            ->where('direction', 'out')
            ->sum('amount');

        return round($actual + $forecastIn - $forecastOut, 2);
    }
}
