<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Filament\Clusters\BusinessIntelligence\Widgets;
use App\Models\Equipment;
use App\Models\EdmsDocument;
use App\Models\Material;
use App\Models\Project;
use App\Models\Site;
use App\Models\Staff;
use App\Models\Subcontractor;
use App\Models\Supplier;
use App\Traits\HasModuleAccess;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class ExecutiveDashboard extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Executive Dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Executive Dashboard';

    protected static string $view = 'filament.clusters.business-intelligence.pages.executive-dashboard';

    public string $search = '';

    /**
     * Filament renders getHeaderWidgets() BEFORE the page's own view content,
     * so the custom title banner AND the enterprise search bar must be
     * returned here (not placed in the view body) to actually appear at the
     * top of the dashboard as designed, above every widget.
     */
    public function getHeader(): ?View
    {
        return view('filament.clusters.business-intelligence.pages.executive-dashboard-header', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => static::$title,
            'subtitle' => 'Consolidated view across every construction module — budgets, safety, quality, workforce, equipment and more.',
            'search' => $this->search,
            'results' => $this->getSearchResults(),
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\ExecutiveKpiWidget::class,
            Widgets\BiSiteMapWidget::class,
            Widgets\AiInsightsWidget::class,
            \App\Filament\Widgets\MonthlyProjectProgressChart::class,
            \App\Filament\Widgets\BudgetUtilizationChart::class,
            Widgets\CostTrendChart::class,
            Widgets\ProfitTrendChart::class,
            Widgets\CashFlowTrendChart::class,
            \App\Filament\Widgets\ConstructionRevenueTrendChart::class,
            \App\Filament\Widgets\EquipmentUtilizationChart::class,
            \App\Filament\Widgets\LabourProductivityChart::class,
            \App\Filament\Widgets\MaterialConsumptionChart::class,
            Widgets\SubcontractorPerformanceChart::class,
            Widgets\QualityPerformanceChart::class,
            \App\Filament\Widgets\SafetyIncidentsChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    public function getSearchResults(): array
    {
        $term = trim($this->search);

        if ($term === '') {
            return [];
        }

        $companyId = Auth::user()?->company_id;
        $like = '%' . $term . '%';
        $results = [];

        $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('name', 'like', $like)->limit(5)->get();
        if ($projects->isNotEmpty()) {
            $results['Projects'] = $projects->map(fn (Project $p) => ['label' => $p->name, 'meta' => $p->status])->all();
        }

        $sites = Site::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('name', 'like', $like)->limit(5)->get();
        if ($sites->isNotEmpty()) {
            $results['Sites'] = $sites->map(fn (Site $s) => ['label' => $s->name, 'meta' => $s->status])->all();
        }

        $staff = Staff::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('full_name', 'like', $like)->limit(5)->get();
        if ($staff->isNotEmpty()) {
            $results['Staff'] = $staff->map(fn (Staff $s) => ['label' => $s->full_name, 'meta' => $s->status ?? ''])->all();
        }

        $equipment = Equipment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('name', 'like', $like)->limit(5)->get();
        if ($equipment->isNotEmpty()) {
            $results['Equipment'] = $equipment->map(fn (Equipment $e) => ['label' => $e->name, 'meta' => $e->status])->all();
        }

        $suppliers = Supplier::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('company_name', 'like', $like)->limit(5)->get();
        if ($suppliers->isNotEmpty()) {
            $results['Suppliers'] = $suppliers->map(fn (Supplier $s) => ['label' => $s->company_name, 'meta' => $s->status ?? ''])->all();
        }

        $subcontractors = Subcontractor::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('company_name', 'like', $like)->limit(5)->get();
        if ($subcontractors->isNotEmpty()) {
            $results['Subcontractors'] = $subcontractors->map(fn (Subcontractor $s) => ['label' => $s->company_name, 'meta' => $s->status ?? ''])->all();
        }

        $materials = Material::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('name', 'like', $like)->limit(5)->get();
        if ($materials->isNotEmpty()) {
            $results['Materials'] = $materials->map(fn (Material $m) => ['label' => $m->name, 'meta' => ''])->all();
        }

        $documents = EdmsDocument::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('title', 'like', $like)->limit(5)->get();
        if ($documents->isNotEmpty()) {
            $results['Documents'] = $documents->map(fn (EdmsDocument $d) => ['label' => $d->title, 'meta' => $d->status ?? ''])->all();
        }

        return $results;
    }
}
