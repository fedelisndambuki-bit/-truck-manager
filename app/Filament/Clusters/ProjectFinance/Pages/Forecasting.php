<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Models\Project;
use App\Models\ProjectForecast;
use App\Services\Construction\ProjectFinanceService;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Forecasting extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'project_finance.view';
    }

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Forecasting';

    protected static ?int $navigationSort = 14;

    protected static ?string $title = 'Forecasting';

    protected static string $view = 'filament.clusters.project-finance.pages.forecasting';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Clusters\ProjectFinance\Widgets\ForecastAccuracyChart::class,
        ];
    }

    public function getRows(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        return Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function (Project $project) use ($service) {
                return [
                    'project' => $project,
                    'bac' => $service->budgetAtCompletion($project),
                    'ac' => $service->totalActualCost($project),
                    'eac' => $service->estimateAtCompletion($project),
                    'cpi' => $service->costPerformanceIndex($project),
                    'spi' => $service->schedulePerformanceIndex($project),
                    'estimated_completion' => $project->end_date,
                    'latest_forecast' => $project->forecasts()->latest('forecast_date')->first(),
                ];
            })
            ->all();
    }

    public function generateSnapshot(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $service = app(ProjectFinanceService::class);

        ProjectForecast::create([
            'project_id' => $project->id,
            'forecast_date' => now(),
            'forecast_final_cost' => $service->estimateAtCompletion($project),
            'forecast_revenue' => (float) ($project->expected_revenue ?: $project->contract_value ?: 0),
            'forecast_profit' => (float) ($project->expected_revenue ?: $project->contract_value ?: 0) - $service->estimateAtCompletion($project),
            'estimated_completion_date' => $project->end_date,
            'cost_performance_index' => $service->costPerformanceIndex($project),
            'schedule_performance_index' => $service->schedulePerformanceIndex($project),
        ]);

        Notification::make()->title('Forecast snapshot generated for ' . $project->name)->success()->send();
    }
}
