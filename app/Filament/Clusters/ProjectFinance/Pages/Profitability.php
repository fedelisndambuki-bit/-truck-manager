<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Profitability extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-scale';

    protected static ?string $navigationLabel = 'Profitability';

    protected static ?int $navigationSort = 17;

    protected static ?string $title = 'Profitability';

    protected static string $view = 'filament.clusters.project-finance.pages.profitability';

    public function getRows(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        return Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function (Project $project) use ($service) {
                $revenue = $service->totalRevenue($project);
                $cost = $service->totalActualCost($project);
                $profit = $revenue - $cost;

                return [
                    'project' => $project,
                    'revenue' => $revenue,
                    'cost' => $cost,
                    'profit' => $profit,
                    'gross_margin' => $revenue > 0 ? round(($profit / $revenue) * 100, 1) : 0,
                    'ev' => $service->earnedValue($project),
                    'pv' => $service->plannedValue($project),
                    'cpi' => $service->costPerformanceIndex($project),
                    'spi' => $service->schedulePerformanceIndex($project),
                    'cv' => $service->costVariance($project),
                    'sv' => $service->scheduleVariance($project),
                ];
            })
            ->all();
    }
}
