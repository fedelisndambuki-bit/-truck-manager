<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Models\Project;
use App\Models\RevenueRecognition as RevenueRecognitionModel;
use App\Services\Construction\ProjectFinanceService;
use App\Traits\HasModuleAccess;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class RevenueRecognition extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Revenue Recognition';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Revenue Recognition';

    protected static string $view = 'filament.clusters.project-finance.pages.revenue-recognition';

    public function getRows(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        return Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function (Project $project) use ($service) {
                $contractRevenue = (float) ($project->contract_value ?: $project->expected_revenue ?: $project->budget ?: 0);
                $percentComplete = $service->percentComplete($project);
                $earned = round($contractRevenue * $percentComplete / 100, 2);
                $recognized = $service->totalRevenue($project);
                $deferred = max(0, $earned - $recognized);

                return [
                    'project' => $project,
                    'percent_complete' => $percentComplete,
                    'contract_revenue' => $contractRevenue,
                    'earned_revenue' => $earned,
                    'recognized_revenue' => $recognized,
                    'deferred_revenue' => $deferred,
                ];
            })
            ->all();
    }

    public function recordPeriod(int $projectId): void
    {
        $project = Project::findOrFail($projectId);
        $service = app(ProjectFinanceService::class);

        $contractRevenue = (float) ($project->contract_value ?: $project->expected_revenue ?: $project->budget ?: 0);
        $percentComplete = $service->percentComplete($project);
        $earned = round($contractRevenue * $percentComplete / 100, 2);
        $recognized = $service->totalRevenue($project);

        RevenueRecognitionModel::create([
            'project_id' => $project->id,
            'period_month' => now()->startOfMonth(),
            'method' => 'percentage_of_completion',
            'percent_complete' => $percentComplete,
            'contract_revenue' => $contractRevenue,
            'earned_revenue' => $earned,
            'recognized_revenue' => $recognized,
            'deferred_revenue' => max(0, $earned - $recognized),
        ]);

        Notification::make()->title('Revenue recognition recorded for ' . $project->name)->success()->send();
    }
}
