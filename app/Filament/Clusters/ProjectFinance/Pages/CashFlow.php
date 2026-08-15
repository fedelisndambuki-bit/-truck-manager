<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashFlow extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-up';

    protected static ?string $navigationLabel = 'Cash Flow';

    protected static ?int $navigationSort = 15;

    protected static ?string $title = 'Cash Flow';

    protected static string $view = 'filament.clusters.project-finance.pages.cash-flow';

    public function getMonthlyRows(): array
    {
        $companyId = Auth::user()?->company_id;

        $cashIn = \App\Models\Payment::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')->where('status', 'Paid')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(DB::raw(\App\Support\SqlDateFormat::monthly('created_at', 'month')), DB::raw('sum(amount) as total'))
            ->groupBy('month')->orderBy('month')->pluck('total', 'month');

        $cashOut = \App\Models\Expense::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(DB::raw(\App\Support\SqlDateFormat::monthly('created_at', 'month')), DB::raw('sum(amount) as total'))
            ->groupBy('month')->orderBy('month')->pluck('total', 'month');

        $months = collect($cashIn->keys())->merge($cashOut->keys())->unique()->sort()->values();

        return $months->map(function ($month) use ($cashIn, $cashOut) {
            $in = (float) ($cashIn[$month] ?? 0);
            $out = (float) ($cashOut[$month] ?? 0);

            return ['month' => $month, 'in' => $in, 'out' => $out, 'net' => $in - $out];
        })->all();
    }

    public function getProjectRows(): array
    {
        $companyId = Auth::user()?->company_id;
        $service = app(ProjectFinanceService::class);

        return Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->map(fn (Project $project) => [
                'project' => $project,
                'cash_in' => $service->cashIn($project),
                'cash_out' => $service->cashOut($project),
                'outstanding' => $service->outstandingClientAmount($project),
                'projected' => $service->projectedCashFlow($project),
            ])
            ->all();
    }
}
