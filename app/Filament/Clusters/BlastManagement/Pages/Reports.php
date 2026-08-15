<?php

namespace App\Filament\Clusters\BlastManagement\Pages;

use App\Filament\Clusters\BlastManagement;
use App\Models\BlastCompliance;
use App\Models\BlastDrillHole;
use App\Models\BlastEnvironmentalReading;
use App\Models\BlastExecution;
use App\Models\BlastPlan;
use App\Models\ExplosiveIssue;
use App\Models\FragmentationAnalysis;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\BlastAnalyticsService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Reports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'blast.export';
    }

    protected static ?string $cluster = BlastManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.blast-management.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Blast Reports',
            'subtitle' => 'Blast register, performance, explosive consumption, fragmentation, environmental, compliance, cost, drilling and executive summary reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Blast Register', 'description' => 'Every blast plan with status, priority and site.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportBlastRegister'],
            ['label' => 'Blast Performance Report', 'description' => 'Executed blasts with outcome and duration.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportBlastPerformanceReport'],
            ['label' => 'Explosive Consumption Report', 'description' => 'Explosive issues, quantities used and remaining stock.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExplosiveConsumptionReport'],
            ['label' => 'Fragmentation Report', 'description' => 'Fragmentation size, oversized % and fines % by blast.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportFragmentationReport'],
            ['label' => 'Environmental Report', 'description' => 'Vibration, overpressure, dust and noise readings by blast.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportEnvironmentalReport'],
            ['label' => 'Compliance Report', 'description' => 'Permit, regulatory, safety and environmental checklist status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportComplianceReport'],
            ['label' => 'Blast Cost Report', 'description' => 'Full cost breakdown and total cost by blast.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportBlastCostReport'],
            ['label' => 'Drilling Report', 'description' => 'Drill holes by rig, operator and blast.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDrillingReport'],
            ['label' => 'Executive Blast Summary', 'description' => 'KPI snapshot across production, safety, cost and compliance.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveSummary'],
        ];
    }

    public function exportBlastRegister()
    {
        $rows = BlastPlan::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('extractionSite')
            ->latest('date_planned')
            ->get()
            ->map(fn (BlastPlan $p) => [$p->blast_number, $p->blast_name, $p->extractionSite?->name, $p->date_planned->format('d M Y'), $p->priority, $p->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Blast Register', [
            ['title' => 'All Blast Plans', 'columns' => ['Blast #', 'Name', 'Quarry', 'Date Planned', 'Priority', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportBlastPerformanceReport()
    {
        $rows = BlastExecution::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('blastPlan')
            ->latest('actual_date')
            ->get()
            ->map(fn (BlastExecution $e) => [
                $e->blastPlan?->blast_number, optional($e->actual_date)->format('d M Y'), $e->blast_outcome,
                $e->blast_duration_seconds, $e->explosives_used,
            ])->all();

        return app(ReportExportService::class)->toPdf('Blast Performance Report', [
            ['title' => 'Blast Executions', 'columns' => ['Blast #', 'Date', 'Outcome', 'Duration (s)', 'Explosives Used (kg)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportExplosiveConsumptionReport()
    {
        $rows = ExplosiveIssue::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->latest('issue_date')
            ->get()
            ->map(fn (ExplosiveIssue $i) => [$i->explosive_type, $i->batch_number, $i->quantity_issued, $i->quantity_used, $i->remaining_quantity])
            ->all();

        return app(ReportExportService::class)->toPdf('Explosive Consumption Report', [
            ['title' => 'Explosive Issues', 'columns' => ['Type', 'Batch #', 'Issued (kg)', 'Used (kg)', 'Remaining (kg)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportFragmentationReport()
    {
        $rows = FragmentationAnalysis::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('blastPlan')
            ->get()
            ->map(fn (FragmentationAnalysis $f) => [
                $f->blastPlan?->blast_number, $f->average_fragment_size, $f->oversized_percentage, $f->fines_percentage, $f->crusher_feed_suitability,
            ])->all();

        return app(ReportExportService::class)->toPdf('Fragmentation Report', [
            ['title' => 'Fragmentation Analyses', 'columns' => ['Blast #', 'Avg Fragment (cm)', 'Oversized %', 'Fines %', 'Crusher Feed Suitability'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportEnvironmentalReport()
    {
        $rows = BlastEnvironmentalReading::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('blastPlan')
            ->latest('recorded_at')
            ->get()
            ->map(fn (BlastEnvironmentalReading $r) => [
                $r->blastPlan?->blast_number, $r->ground_vibration, $r->air_overpressure, $r->dust_level, $r->noise_level, $r->compliance_status,
            ])->all();

        return app(ReportExportService::class)->toPdf('Environmental Report', [
            ['title' => 'Environmental Readings', 'columns' => ['Blast #', 'Vibration (mm/s)', 'Overpressure (dB)', 'Dust', 'Noise (dB)', 'Compliance'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportComplianceReport()
    {
        $rows = BlastCompliance::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('blastPlan')
            ->get()
            ->map(fn (BlastCompliance $c) => [
                $c->blastPlan?->blast_number, $c->permit_number, optional($c->permit_expiry)->format('d M Y'), $c->scorePercent() . '%',
            ])->all();

        return app(ReportExportService::class)->toPdf('Compliance Report', [
            ['title' => 'Blast Compliance', 'columns' => ['Blast #', 'Permit #', 'Permit Expiry', 'Compliance Score'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportBlastCostReport()
    {
        $costs = \App\Models\BlastCost::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->with('blastPlan')->get();

        $rows = $costs->map(fn ($c) => [
            $c->blastPlan?->blast_number, $c->drilling_cost, $c->explosive_cost, $c->labour_cost, $c->equipment_cost, $c->totalCost(),
        ])->all();

        return app(ReportExportService::class)->toPdf('Blast Cost Report', [
            ['title' => 'Blast Costs', 'columns' => ['Blast #', 'Drilling', 'Explosive', 'Labour', 'Equipment', 'Total'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportDrillingReport()
    {
        $rows = BlastDrillHole::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['blastPlan', 'drillRig', 'operator'])
            ->get()
            ->map(fn (BlastDrillHole $h) => [
                $h->blastPlan?->blast_number, $h->hole_number, $h->diameter, $h->depth, $h->drillRig?->name, $h->operator?->full_name,
            ])->all();

        return app(ReportExportService::class)->toPdf('Drilling Report', [
            ['title' => 'Drill Holes', 'columns' => ['Blast #', 'Hole #', 'Diameter (mm)', 'Depth (m)', 'Drill Rig', 'Operator'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportExecutiveSummary()
    {
        $svc = app(BlastAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Scheduled Blasts', 'value' => (string) $svc->scheduledBlastsCount($companyId)],
            ['label' => 'Completed Blasts', 'value' => (string) $svc->completedBlastsCount($companyId)],
            ['label' => 'Blast Success Rate', 'value' => $svc->blastSuccessRatePercent($companyId) . '%'],
            ['label' => 'Avg Fragmentation Size', 'value' => number_format($svc->avgFragmentationSize($companyId), 1) . ' cm'],
            ['label' => 'Misfires', 'value' => (string) $svc->misfiresCount($companyId)],
            ['label' => 'Safety Incidents', 'value' => (string) $svc->safetyIncidentsFromBlasts($companyId)],
            ['label' => 'Avg Compliance Score', 'value' => $svc->avgComplianceScore($companyId) . '%'],
            ['label' => 'Environmental Performance', 'value' => $svc->environmentalPerformancePercent($companyId) . '%'],
            ['label' => 'Total Blast Cost (90 Days)', 'value' => number_format($svc->totalBlastCost($companyId), 0)],
        ];

        return app(ReportExportService::class)->toPdf('Executive Blast Summary', [], $kpis, $companyId);
    }
}
