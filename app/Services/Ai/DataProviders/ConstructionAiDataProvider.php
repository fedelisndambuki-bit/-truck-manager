<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Enums\CompanyIndustry;
use App\Models\Project;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ProjectFinanceService;

class ConstructionAiDataProvider implements AiDataProviderInterface
{
    public function __construct(protected BusinessIntelligenceService $bi, protected ProjectFinanceService $finance)
    {
    }

    public function key(): string
    {
        return 'construction';
    }

    public function label(): string
    {
        return 'Construction Projects';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return in_array($industry, [CompanyIndustry::Construction->value, CompanyIndustry::MultiOperation->value], true);
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        $limit = config('ai.max_records_per_provider', 40);

        $projects = Project::where('company_id', $companyId)->take($limit)->get(['id', 'name', 'status']);

        // costVariance/scheduleVariance are Earned-Value-Management style:
        // positive = under budget / ahead of schedule, negative = over
        // budget / behind schedule — the opposite of a naive "bigger number
        // = worse" reading, so the budget_status label is derived
        // explicitly rather than left for the model to infer from the sign.
        $projectRoster = $projects->map(function ($p) {
            $costVariance = $this->finance->costVariance($p);
            $scheduleVariance = $this->finance->scheduleVariance($p);

            return [
                'project' => $p->name,
                'status' => $p->status,
                'actual_cost' => $this->finance->totalActualCost($p),
                'cost_variance' => $costVariance,
                'budget_status' => match (true) {
                    $costVariance < 0 => 'Over budget',
                    $costVariance > 0 => 'Under budget',
                    default => 'On budget',
                },
                'schedule_variance' => $scheduleVariance,
                'schedule_status' => match (true) {
                    $scheduleVariance < 0 => 'Behind schedule',
                    $scheduleVariance > 0 => 'Ahead of schedule',
                    default => 'On schedule',
                },
            ];
        })->sortByDesc('actual_cost')->values()->all();

        return [
            'total_projects' => $this->bi->totalProjects($companyId),
            'active_projects' => $this->bi->activeProjects($companyId),
            'projects_at_risk' => $this->bi->projectsAtRisk($companyId),
            'project_roster' => $projectRoster,
            'total_contract_value' => $this->bi->totalContractValue($companyId),
            'total_revenue' => $this->bi->totalRevenue($companyId),
            'total_cost' => $this->bi->totalCost($companyId),
            'profit' => $this->bi->profit($companyId),
            'profit_margin_percent' => $this->bi->profitMargin($companyId),
            'budget_utilization_percent' => $this->bi->budgetUtilization($companyId),
            'cash_flow_position' => $this->bi->cashFlowPosition($companyId),
            'workforce_count' => $this->bi->workforceCount($companyId),
            'equipment_utilization_percent' => $this->bi->equipmentUtilization($companyId),
            'material_cost' => $this->bi->materialCost($companyId),
            'safety_score' => $this->bi->safetyScore($companyId),
            'project_completion_rate_percent' => $this->bi->projectCompletionRate($companyId),
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_project_summary',
                    'description' => 'Get construction project counts, a per-project roster ranked by actual cost (including cost/schedule variance and over/under-budget status per project), portfolio-wide financial performance (revenue/cost/profit/margin), budget utilization, workforce and safety score.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
        ];
    }

    public function callTool(string $name, array $arguments, int $companyId): array
    {
        return $this->summary($companyId);
    }
}
