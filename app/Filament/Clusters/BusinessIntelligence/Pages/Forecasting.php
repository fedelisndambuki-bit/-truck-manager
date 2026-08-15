<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\EquipmentAllocation;
use App\Models\Labourer;
use App\Models\MaterialConsumption;
use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class Forecasting extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'bi.view';
    }

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';

    protected static ?string $navigationLabel = 'Forecasting';

    protected static ?int $navigationSort = 14;

    protected static ?string $title = 'Forecasting';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Project-level forecasts reuse the Earned Value Management engine from Project Finance; resource forecasts use linear trend projection from recent consumption.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />';
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Project Completion Forecast', 'description' => 'New: estimate-at-completion date & cost per project (EVM).', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCompletionForecast'],
            ['label' => 'Cash Flow Forecast', 'description' => 'New: projected cash position per project.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCashFlowForecast'],
            ['label' => 'Budget Overrun Forecast', 'description' => 'New: projects projected to exceed budget at completion.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportBudgetOverrunForecast'],
            ['label' => 'Profit Forecast', 'description' => 'New: projected final profit per project.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportProfitForecast'],
            ['label' => 'Revenue Forecast', 'description' => 'New: projected revenue based on current progress.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportRevenueForecast'],
            ['label' => 'Equipment Demand Forecast', 'description' => 'New: projected equipment allocations next 30 days (trend).', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportEquipmentDemandForecast'],
            ['label' => 'Labour Requirements Forecast', 'description' => 'New: projected headcount next month (trend).', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportLabourForecast'],
            ['label' => 'Material Consumption Forecast', 'description' => 'New: projected material spend next month (trend).', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportMaterialForecast'],
        ];
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    protected function projects()
    {
        return Project::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->where('status', 'Active')->get();
    }

    protected function currency(): string
    {
        return session('currency', 'KES');
    }

    public function exportCompletionForecast()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name, $p->progress_percent . '%', number_format($finance->schedulePerformanceIndex($p), 2),
            $currency . ' ' . number_format($finance->estimateAtCompletion($p), 0),
        ])->all();

        return app(ReportExportService::class)->toPdf('Project Completion Forecast', [
            ['title' => 'Estimate at Completion (EVM)', 'columns' => ['Project', 'Progress', 'SPI', 'Estimate at Completion'], 'rows' => $rows],
        ]);
    }

    public function exportCashFlowForecast()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()->map(fn (Project $p) => [$p->name, $currency . ' ' . number_format($finance->projectedCashFlow($p), 0)])->all();

        return app(ReportExportService::class)->toPdf('Cash Flow Forecast', [
            ['title' => 'Projected Cash Position', 'columns' => ['Project', 'Projected Cash Flow'], 'rows' => $rows],
        ]);
    }

    public function exportBudgetOverrunForecast()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()
            ->filter(fn (Project $p) => $finance->estimateAtCompletion($p) > $finance->budgetAtCompletion($p))
            ->map(fn (Project $p) => [
                $p->name, $currency . ' ' . number_format($finance->budgetAtCompletion($p), 0),
                $currency . ' ' . number_format($finance->estimateAtCompletion($p), 0),
                $currency . ' ' . number_format($finance->estimateAtCompletion($p) - $finance->budgetAtCompletion($p), 0),
            ])->values()->all();

        return app(ReportExportService::class)->toPdf('Budget Overrun Forecast', [
            ['title' => 'Projects Trending Over Budget', 'columns' => ['Project', 'Budget', 'Estimate at Completion', 'Projected Overrun'], 'rows' => $rows],
        ]);
    }

    public function exportProfitForecast()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()->map(function (Project $p) use ($finance, $currency) {
            $projectedProfit = $finance->totalRevenue($p) - $finance->estimateAtCompletion($p);

            return [$p->name, $currency . ' ' . number_format($projectedProfit, 0)];
        })->all();

        return app(ReportExportService::class)->toPdf('Profit Forecast', [
            ['title' => 'Projected Final Profit', 'columns' => ['Project', 'Projected Profit'], 'rows' => $rows],
        ]);
    }

    public function exportRevenueForecast()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()->map(function (Project $p) use ($finance, $currency) {
            $projected = $p->progress_percent > 0 ? ($p->contract_value * ($p->progress_percent / 100)) : 0;

            return [$p->name, $currency . ' ' . number_format($finance->totalRevenue($p), 0), $currency . ' ' . number_format($projected, 0)];
        })->all();

        return app(ReportExportService::class)->toPdf('Revenue Forecast', [
            ['title' => 'Revenue vs Progress-Based Projection', 'columns' => ['Project', 'Revenue to Date', 'Projected Revenue'], 'rows' => $rows],
        ]);
    }

    public function exportEquipmentDemandForecast()
    {
        $companyId = $this->companyId();

        $weeks = collect(range(3, 0))->map(function (int $i) use ($companyId) {
            $start = now()->subWeeks($i + 1);
            $end = now()->subWeeks($i);

            return EquipmentAllocation::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereBetween('allocated_from', [$start, $end])->count();
        });

        $avgWeekly = $weeks->avg() ?: 0;
        $projected30d = (int) round($avgWeekly * 4.3);

        $rows = $weeks->map(fn ($count, $i) => ['Week ' . ($i + 1), $count])->values()->all();
        $rows[] = ['Projected Next 30 Days', $projected30d];

        return app(ReportExportService::class)->toPdf('Equipment Demand Forecast', [
            ['title' => 'New Allocations Trend (last 4 weeks + projection)', 'columns' => ['Period', 'Allocations'], 'rows' => $rows],
        ]);
    }

    public function exportLabourForecast()
    {
        $companyId = $this->companyId();

        $months = collect(range(2, 0))->map(function (int $i) use ($companyId) {
            $month = now()->subMonths($i);

            return Labourer::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereYear('created_at', '<=', $month->year)
                ->whereDate('created_at', '<=', $month->endOfMonth())
                ->count();
        });

        $netChange = $months->count() > 1 ? round(($months->last() - $months->first()) / max(1, $months->count() - 1)) : 0;
        $projected = max(0, $months->last() + $netChange);

        $rows = $months->map(fn ($count, $i) => [now()->subMonths(2 - $i)->format('M Y'), $count])->values()->all();
        $rows[] = ['Projected Next Month', $projected];

        return app(ReportExportService::class)->toPdf('Labour Requirements Forecast', [
            ['title' => 'Headcount Trend (last 3 months + projection)', 'columns' => ['Month', 'Headcount'], 'rows' => $rows],
        ]);
    }

    public function exportMaterialForecast()
    {
        $companyId = $this->companyId();
        $currency = $this->currency();

        $months = collect(range(2, 0))->map(function (int $i) use ($companyId) {
            $month = now()->subMonths($i);

            return (float) MaterialConsumption::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereYear('date', $month->year)->whereMonth('date', $month->month)
                ->sum('cost');
        });

        $avg = $months->avg() ?: 0;
        $trend = $months->count() > 1 ? ($months->last() - $months->first()) / max(1, $months->count() - 1) : 0;
        $projected = max(0, $avg + $trend);

        $rows = $months->map(fn ($cost, $i) => [now()->subMonths(2 - $i)->format('M Y'), $currency . ' ' . number_format($cost, 0)])->values()->all();
        $rows[] = ['Projected Next Month', $currency . ' ' . number_format($projected, 0)];

        return app(ReportExportService::class)->toPdf('Material Consumption Forecast', [
            ['title' => 'Material Spend Trend (last 3 months + projection)', 'columns' => ['Month', 'Cost'], 'rows' => $rows],
        ]);
    }
}
