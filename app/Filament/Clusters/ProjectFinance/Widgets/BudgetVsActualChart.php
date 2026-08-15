<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class BudgetVsActualChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Budget vs Actual';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        try {
            $projects = Project::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->orderByDesc('contract_value')
                ->limit(6)
                ->get();

            $labels = [];
            $budgets = [];
            $actuals = [];

            foreach ($projects as $project) {
                $labels[] = str($project->name)->limit(14)->toString();
                $budgets[] = (float) ($project->estimated_cost ?: $project->budget ?: $project->contract_value ?: 0);
                $actuals[] = $service->totalActualCost($project);
            }
        } catch (\Exception $e) {
            $labels = [];
            $budgets = [];
            $actuals = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No projects'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Budget', 'data' => $budgets, 'backgroundColor' => '#2563eb'],
                ['label' => 'Actual', 'data' => $actuals, 'backgroundColor' => '#f59e0b'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
