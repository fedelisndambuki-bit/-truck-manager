<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class EquipmentCostChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Equipment Cost by Project';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        try {
            $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->limit(6)->get();
            $labels = [];
            $values = [];

            foreach ($projects as $project) {
                $cost = $service->equipmentCost($project);
                if ($cost <= 0) {
                    continue;
                }
                $labels[] = str($project->name)->limit(14)->toString();
                $values[] = $cost;
            }
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Equipment Cost', 'data' => $values, 'backgroundColor' => '#f59e0b']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
