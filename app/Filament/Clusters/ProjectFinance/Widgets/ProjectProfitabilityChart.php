<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ProjectProfitabilityChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Project Profitability';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        try {
            $projects = Project::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->limit(8)->get();

            $labels = [];
            $profits = [];

            foreach ($projects as $project) {
                $labels[] = str($project->name)->limit(14)->toString();
                $profits[] = round($service->totalRevenue($project) - $service->totalActualCost($project), 2);
            }
        } catch (\Exception $e) {
            $labels = [];
            $profits = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No projects'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Profit', 'data' => $profits,
                'backgroundColor' => array_map(fn ($p) => $p >= 0 ? '#10b981' : '#ef4444', $profits),
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
