<?php

namespace App\Services\Construction;

use App\Models\Attendance;
use App\Models\ClientSatisfactionRating;
use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Models\Labourer;
use App\Models\MaterialConsumption;
use App\Models\Project;
use App\Models\QualityInspection;
use App\Models\SafetyIncident;
use App\Models\Site;
use Illuminate\Support\Facades\Auth;

/**
 * Orchestration layer for the Executive Dashboard, Forecasting, and Custom
 * Report data sources — composes ProjectFinanceService/DocumentManagementService
 * rather than re-deriving their cost/document logic, and queries the
 * Labour/Equipment/Materials/Safety/Quality models directly (none of those
 * modules have a dedicated service, matching the app-wide convention).
 */
class BusinessIntelligenceService
{
    public function __construct(protected ProjectFinanceService $finance)
    {
    }

    protected function projects(?int $companyId)
    {
        return Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Executive KPIs ───────────────────────────────────────────────────────

    public function totalProjects(?int $companyId): int
    {
        return $this->projects($companyId)->count();
    }

    public function activeProjects(?int $companyId): int
    {
        return $this->projects($companyId)->where('status', 'Active')->count();
    }

    public function projectsAtRisk(?int $companyId): int
    {
        $count = 0;

        $this->projects($companyId)->get()->each(function (Project $project) use (&$count) {
            if ($this->finance->costPerformanceIndex($project) < 0.9
                || $this->finance->schedulePerformanceIndex($project) < 0.9
                || $project->status === 'Delayed') {
                $count++;
            }
        });

        return $count;
    }

    public function totalContractValue(?int $companyId): float
    {
        return (float) $this->projects($companyId)->sum('contract_value');
    }

    public function totalRevenue(?int $companyId): float
    {
        return round($this->projects($companyId)->get()->sum(fn (Project $p) => $this->finance->totalRevenue($p)), 2);
    }

    public function totalCost(?int $companyId): float
    {
        return round($this->projects($companyId)->get()->sum(fn (Project $p) => $this->finance->totalActualCost($p)), 2);
    }

    public function profit(?int $companyId): float
    {
        return round($this->totalRevenue($companyId) - $this->totalCost($companyId), 2);
    }

    public function profitMargin(?int $companyId): float
    {
        $revenue = $this->totalRevenue($companyId);

        return $revenue > 0 ? round(($this->profit($companyId) / $revenue) * 100, 1) : 0.0;
    }

    public function budgetUtilization(?int $companyId): float
    {
        $budget = (float) $this->projects($companyId)->get()->sum(fn (Project $p) => $this->finance->budgetAtCompletion($p));

        return $budget > 0 ? round(($this->totalCost($companyId) / $budget) * 100, 1) : 0.0;
    }

    public function cashFlowPosition(?int $companyId): float
    {
        return round($this->projects($companyId)->get()->sum(fn (Project $p) => $this->finance->projectedCashFlow($p)), 2);
    }

    public function workforceCount(?int $companyId): int
    {
        return Labourer::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
    }

    public function equipmentUtilization(?int $companyId): float
    {
        $total = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        if ($total <= 0) {
            return 0.0;
        }

        $inUse = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'In Use')->count();

        return round(($inUse / $total) * 100, 1);
    }

    public function materialCost(?int $companyId): float
    {
        return round((float) MaterialConsumption::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->sum('cost'), 2);
    }

    /**
     * 100 minus severity-weighted points for currently-open incidents,
     * floored at 0 — a transparent, simplified safety scorecard (there is no
     * industry-standard formula baked into this codebase to defer to).
     */
    public function safetyScore(?int $companyId): float
    {
        $open = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Open', 'Investigating'])
            ->get();

        $weights = ['Critical' => 15, 'High' => 8, 'Medium' => 3, 'Low' => 1];
        $penalty = $open->sum(fn (SafetyIncident $i) => $weights[$i->severity] ?? 1);

        return max(0.0, round(100 - $penalty, 1));
    }

    public function qualityScore(?int $companyId, int $withinDays = 90): float
    {
        $inspections = QualityInspection::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('date', '>=', now()->subDays($withinDays))
            ->get();

        if ($inspections->isEmpty()) {
            return 0.0;
        }

        return round(($inspections->where('result', 'Pass')->count() / $inspections->count()) * 100, 1);
    }

    public function clientSatisfaction(?int $companyId): ?float
    {
        $avg = ClientSatisfactionRating::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->avg('score');

        return $avg ? round(((float) $avg / 5) * 100, 1) : null;
    }

    public function projectCompletionRate(?int $companyId): float
    {
        $total = $this->projects($companyId)->count();

        if ($total <= 0) {
            return 0.0;
        }

        $completed = $this->projects($companyId)->where('status', 'Completed')->count();

        return round(($completed / $total) * 100, 1);
    }

    // ── Site-level helpers (used by Site Reports / Interactive Map) ─────────

    public function siteWorkforceCount(Site $site): int
    {
        return Labourer::query()->where('site_id', $site->id)->count();
    }

    public function siteEquipmentCount(Site $site): int
    {
        return EquipmentAllocation::query()->where('site_id', $site->id)->whereNull('actual_return_date')->count();
    }

    public function siteAttendanceRate(Site $site, int $days = 7): float
    {
        $records = Attendance::query()
            ->where('site_id', $site->id)
            ->where('date', '>=', now()->subDays($days))
            ->get();

        if ($records->isEmpty()) {
            return 0.0;
        }

        return round(($records->where('status', 'Present')->count() / $records->count()) * 100, 1);
    }

    // ── Custom Report Builder data-source resolution ────────────────────────

    public function dataSourceOptions(): array
    {
        return [
            'Projects' => 'Projects', 'Sites' => 'Sites', 'Labour' => 'Labour',
            'Equipment' => 'Equipment', 'Materials' => 'Materials', 'Subcontractors' => 'Subcontractors',
            'Safety' => 'Safety', 'Quality' => 'Quality', 'Finance' => 'Finance', 'Documents' => 'Documents',
            'Journal Entries' => 'Journal Entries', 'Chart of Accounts' => 'Chart of Accounts',
            'Leads' => 'Leads', 'Opportunities' => 'Opportunities', 'Quotations' => 'Quotations',
            'Warehouses' => 'Warehouses', 'Stock Levels' => 'Stock Levels',
        ];
    }

    /**
     * Whitelisted model + column map per data source — the ONLY thing a
     * Custom Report is allowed to query. Never accepts raw user SQL.
     */
    public function dataSourceDefinition(string $source): ?array
    {
        return match ($source) {
            'Projects' => ['model' => \App\Models\Project::class, 'columns' => ['id', 'name', 'status', 'contract_value', 'budget', 'progress_percent', 'start_date', 'end_date']],
            'Sites' => ['model' => \App\Models\Site::class, 'columns' => ['id', 'name', 'status', 'progress_percent']],
            'Labour' => ['model' => \App\Models\Labourer::class, 'columns' => ['id', 'role_on_site', 'daily_rate']],
            'Equipment' => ['model' => \App\Models\Equipment::class, 'columns' => ['id', 'name', 'equipment_type', 'status']],
            'Materials' => ['model' => \App\Models\Material::class, 'columns' => ['id', 'name', 'unit_cost', 'quantity_on_hand']],
            'Subcontractors' => ['model' => \App\Models\Subcontractor::class, 'columns' => ['id', 'company_name', 'status']],
            'Safety' => ['model' => \App\Models\SafetyIncident::class, 'columns' => ['id', 'incident_number', 'severity', 'status', 'date']],
            'Quality' => ['model' => \App\Models\QualityInspection::class, 'columns' => ['id', 'checklist_item', 'result', 'date']],
            'Finance' => ['model' => \App\Models\ProjectBudget::class, 'columns' => ['id', 'budget_number', 'category', 'approved_budget', 'actual_cost', 'status']],
            'Documents' => ['model' => \App\Models\EdmsDocument::class, 'columns' => ['id', 'document_number', 'title', 'category', 'status']],
            'Journal Entries' => ['model' => \App\Models\JournalEntry::class, 'columns' => ['id', 'journal_number', 'date', 'status', 'description']],
            'Chart of Accounts' => ['model' => \App\Models\ChartOfAccount::class, 'columns' => ['id', 'account_code', 'name', 'type', 'status']],
            'Leads' => ['model' => \App\Models\Lead::class, 'columns' => ['id', 'lead_number', 'company_name', 'status', 'estimated_value', 'priority']],
            'Opportunities' => ['model' => \App\Models\Opportunity::class, 'columns' => ['id', 'opportunity_number', 'stage', 'estimated_revenue', 'probability']],
            'Quotations' => ['model' => \App\Models\Quotation::class, 'columns' => ['id', 'quotation_number', 'status', 'total', 'validity_date']],
            'Warehouses' => ['model' => \App\Models\Warehouse::class, 'columns' => ['id', 'name', 'type', 'capacity', 'status']],
            'Stock Levels' => ['model' => \App\Models\StockLevel::class, 'columns' => ['id', 'quantity_on_hand', 'reserved_quantity', 'expiry_date']],
            default => null,
        };
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }
}
