<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\Milestone;
use App\Models\Project;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ProjectFinanceService;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class ProjectReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationLabel = 'Project Reports';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'Project Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Project summary, status, milestones, delays, budget performance, profitability, resource utilization, timeline and executive summary.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.375c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21" />';
    }

    public function getHubCards(): array
    {
        $defs = [
            ['project_summary' => 'Project Summary'], ['project_status' => 'Project Status'],
            ['milestones' => 'Milestones'], ['project_delays' => 'Project Delays'],
            ['budget_performance' => 'Budget Performance'], ['profitability' => 'Profitability'],
            ['resource_utilization' => 'Resource Utilization'], ['project_timeline' => 'Project Timeline'],
            ['executive_summary' => 'Executive Summary'],
        ];

        return collect($defs)->map(function ($def) {
            $key = array_key_first($def);

            return [
                'label' => $def[$key],
                'description' => 'New report generated live from Project + Project Finance data.',
                'icon' => 'heroicon-o-document-arrow-down',
                'action' => 'export' . str($key)->studly(),
            ];
        })->all();
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    protected function projects()
    {
        return Project::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->get();
    }

    protected function currency(): string
    {
        return session('currency', 'KES');
    }

    public function exportProjectSummary()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name, $p->status, $currency . ' ' . number_format((float) $p->contract_value, 0),
            $p->progress_percent . '%', optional($p->start_date)->format('d M Y'), optional($p->end_date)->format('d M Y'),
        ])->all();

        return app(ReportExportService::class)->toPdf('Project Summary', [
            ['title' => 'All Projects', 'columns' => ['Project', 'Status', 'Contract Value', 'Progress', 'Start', 'End'], 'rows' => $rows],
        ]);
    }

    public function exportProjectStatus()
    {
        $rows = $this->projects()->groupBy('status')->map(fn ($group, $status) => [$status, $group->count()])->values()->all();

        return app(ReportExportService::class)->toPdf('Project Status Report', [
            ['title' => 'Projects by Status', 'columns' => ['Status', 'Count'], 'rows' => $rows],
        ]);
    }

    public function exportMilestones()
    {
        $rows = Milestone::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('project')->get()
            ->map(fn (Milestone $m) => [$m->project?->name, $m->name, optional($m->planned_date)->format('d M Y'), optional($m->actual_date)->format('d M Y'), $m->status, $m->is_critical_path ? 'Yes' : 'No'])
            ->all();

        return app(ReportExportService::class)->toPdf('Milestones Report', [
            ['title' => 'Project Milestones', 'columns' => ['Project', 'Milestone', 'Planned', 'Actual', 'Status', 'Critical Path'], 'rows' => $rows],
        ]);
    }

    public function exportProjectDelays()
    {
        $rows = Milestone::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Delayed')
            ->with('project')->get()
            ->map(fn (Milestone $m) => [$m->project?->name, $m->name, optional($m->planned_date)->format('d M Y'), $m->planned_date ? now()->diffInDays($m->planned_date) . ' days' : '—'])
            ->all();

        return app(ReportExportService::class)->toPdf('Project Delays Report', [
            ['title' => 'Delayed Milestones', 'columns' => ['Project', 'Milestone', 'Planned Date', 'Days Overdue'], 'rows' => $rows],
        ]);
    }

    public function exportBudgetPerformance()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name,
            $currency . ' ' . number_format($finance->budgetAtCompletion($p), 0),
            $currency . ' ' . number_format($finance->totalActualCost($p), 0),
            number_format($finance->costPerformanceIndex($p), 2),
        ])->all();

        return app(ReportExportService::class)->toPdf('Budget Performance Report', [
            ['title' => 'Budget vs Actual', 'columns' => ['Project', 'Budget', 'Actual Cost', 'CPI'], 'rows' => $rows],
        ]);
    }

    public function exportProfitability()
    {
        $finance = app(ProjectFinanceService::class);
        $currency = $this->currency();

        $rows = $this->projects()->map(function (Project $p) use ($finance, $currency) {
            $revenue = $finance->totalRevenue($p);
            $cost = $finance->totalActualCost($p);

            return [$p->name, $currency . ' ' . number_format($revenue, 0), $currency . ' ' . number_format($cost, 0), $currency . ' ' . number_format($revenue - $cost, 0)];
        })->all();

        return app(ReportExportService::class)->toPdf('Profitability Report', [
            ['title' => 'Project Profitability', 'columns' => ['Project', 'Revenue', 'Cost', 'Profit'], 'rows' => $rows],
        ]);
    }

    public function exportResourceUtilization()
    {
        $bi = app(BusinessIntelligenceService::class);

        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name,
            \App\Models\Labourer::query()->where('project_id', $p->id)->count(),
            \App\Models\EquipmentAllocation::query()->where('project_id', $p->id)->whereNull('actual_return_date')->count(),
        ])->all();

        return app(ReportExportService::class)->toPdf('Resource Utilization Report', [
            ['title' => 'Labour & Equipment by Project', 'columns' => ['Project', 'Labourers', 'Equipment Allocated'], 'rows' => $rows],
        ]);
    }

    public function exportProjectTimeline()
    {
        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name, optional($p->start_date)->format('d M Y'), optional($p->end_date)->format('d M Y'),
            $p->start_date && $p->end_date ? $p->start_date->diffInDays($p->end_date) . ' days' : '—', $p->progress_percent . '%',
        ])->all();

        return app(ReportExportService::class)->toPdf('Project Timeline Report', [
            ['title' => 'Project Schedule', 'columns' => ['Project', 'Start', 'End', 'Duration', 'Progress'], 'rows' => $rows],
        ]);
    }

    public function exportExecutiveSummary()
    {
        $bi = app(BusinessIntelligenceService::class);
        $companyId = $this->companyId();
        $currency = $this->currency();

        $kpis = [
            ['label' => 'Total Projects', 'value' => (string) $bi->totalProjects($companyId)],
            ['label' => 'Active Projects', 'value' => (string) $bi->activeProjects($companyId)],
            ['label' => 'Projects at Risk', 'value' => (string) $bi->projectsAtRisk($companyId)],
            ['label' => 'Total Contract Value', 'value' => $currency . ' ' . number_format($bi->totalContractValue($companyId), 0)],
            ['label' => 'Profit Margin', 'value' => $bi->profitMargin($companyId) . '%'],
        ];

        $rows = $this->projects()->map(fn (Project $p) => [$p->name, $p->status, $p->progress_percent . '%'])->all();

        return app(ReportExportService::class)->toPdf('Executive Project Summary', [
            ['title' => 'Projects', 'columns' => ['Project', 'Status', 'Progress'], 'rows' => $rows],
        ], $kpis);
    }
}
