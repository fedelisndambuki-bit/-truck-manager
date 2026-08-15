<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class CostControl extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-adjustments-horizontal';

    protected static ?string $navigationLabel = 'Cost Control';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Cost Control';

    protected static string $view = 'filament.clusters.project-finance.pages.cost-control';

    public function getRows(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        return Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(function (Project $project) use ($service) {
                $breakdown = $service->costBreakdown($project);

                return [
                    'project' => $project,
                    'breakdown' => $breakdown,
                    'total' => array_sum($breakdown),
                ];
            })
            ->filter(fn ($row) => $row['total'] > 0)
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    public function getCompanyBreakdown(): array
    {
        return app(ProjectFinanceService::class)->companyCostBreakdown(Auth::user()?->company_id);
    }
}
