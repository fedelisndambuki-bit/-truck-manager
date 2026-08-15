<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Analytics: how closely each project's latest recorded forecast (final cost)
 * tracks its approved budget at completion — 100% means the forecast and the
 * approved budget currently agree. Deliberately compared against BAC rather
 * than cost-to-date, since a part-complete project's actual-to-date is not a
 * like-for-like comparison against a final-cost forecast.
 */
class ForecastAccuracyChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Forecast Accuracy';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        try {
            $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->limit(8)->get();

            $labels = [];
            $accuracy = [];

            foreach ($projects as $project) {
                $forecast = $project->forecasts()->latest('forecast_date')->first();

                if (! $forecast) {
                    continue;
                }

                $bac = $service->budgetAtCompletion($project);
                $forecastCost = (float) $forecast->forecast_final_cost;

                if ($forecastCost <= 0 || $bac <= 0) {
                    continue;
                }

                $variancePct = abs($forecastCost - $bac) / $bac * 100;

                $labels[] = str($project->name)->limit(14)->toString();
                $accuracy[] = round(max(0, 100 - $variancePct), 1);
            }
        } catch (\Exception $e) {
            $labels = [];
            $accuracy = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No forecasts yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Forecast Accuracy %', 'data' => $accuracy,
                'backgroundColor' => array_map(fn ($v) => $v >= 90 ? '#10b981' : ($v >= 75 ? '#f59e0b' : '#ef4444'), $accuracy),
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
