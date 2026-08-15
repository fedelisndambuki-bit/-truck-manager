<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Pages;

use App\Filament\Clusters\CrushingPlantManagement;
use App\Models\Crusher;
use App\Models\EnergyReading;
use App\Models\PlantOperation;
use App\Models\ProductionDowntime;
use App\Models\ProductionOutput;
use App\Models\ProductionRun;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\CrushingPlantAnalyticsService;
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
        return 'plant.export';
    }

    protected static ?string $cluster = CrushingPlantManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 13;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.crushing-plant-management.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Plant Reports',
            'subtitle' => 'Daily, shift, utilization, crusher performance, downtime, energy, product output, OEE and executive plant reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Daily Plant Report', 'description' => 'Plant operations logged today.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDailyPlantReport'],
            ['label' => 'Shift Report', 'description' => 'Plant operations by shift over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportShiftReport'],
            ['label' => 'Plant Utilization Report', 'description' => 'Utilization and status across all plants.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportPlantUtilizationReport'],
            ['label' => 'Crusher Performance Report', 'description' => 'Throughput, operating hours and wear status by crusher.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCrusherPerformanceReport'],
            ['label' => 'Downtime Report', 'description' => 'Plant downtime events by reason and equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDowntimeReport'],
            ['label' => 'Energy Consumption Report', 'description' => 'Electricity, diesel and cost per tonne by plant.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportEnergyConsumptionReport'],
            ['label' => 'Product Output Report', 'description' => 'Product output by run, material and stockpile.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportProductOutputReport'],
            ['label' => 'OEE Report', 'description' => 'Availability, performance, quality and OEE by company.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportOeeReport'],
            ['label' => 'Executive Plant Summary', 'description' => 'KPI snapshot across production, utilization, energy and OEE.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveSummary'],
        ];
    }

    public function exportDailyPlantReport()
    {
        $rows = PlantOperation::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereDate('start_time', today())
            ->with(['crushingPlant', 'operator'])
            ->get()
            ->map(fn (PlantOperation $o) => [
                $o->crushingPlant?->name, $o->shift, $o->operator?->full_name, $o->feed_quantity,
                number_format($o->operatingHours(), 1), $o->downtime_minutes,
            ])->all();

        return app(ReportExportService::class)->toPdf('Daily Plant Report', [
            ['title' => "Plant Operations — " . today()->toFormattedDateString(), 'columns' => ['Plant', 'Shift', 'Operator', 'Feed Qty (t)', 'Operating Hrs', 'Downtime (min)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportShiftReport()
    {
        $rows = PlantOperation::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('start_time', '>=', now()->subDays(30))
            ->get()
            ->groupBy('shift')
            ->map(fn ($group, $shift) => [$shift, $group->count(), number_format((float) $group->sum('feed_quantity'), 1)])
            ->values()->all();

        return app(ReportExportService::class)->toPdf('Shift Report (30 Days)', [
            ['title' => 'Operations by Shift', 'columns' => ['Shift', 'Records', 'Total Feed (t)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportPlantUtilizationReport()
    {
        $svc = app(CrushingPlantAnalyticsService::class);

        $kpis = [
            ['label' => 'Active Plants', 'value' => (string) $svc->activePlantsCount($this->companyId())],
            ['label' => 'Running Plants', 'value' => (string) $svc->runningPlantsCount($this->companyId())],
            ['label' => 'Plant Utilization', 'value' => $svc->plantUtilizationPercent($this->companyId()) . '%'],
        ];

        return app(ReportExportService::class)->toPdf('Plant Utilization Report', [], $kpis, $this->companyId());
    }

    public function exportCrusherPerformanceReport()
    {
        $rows = Crusher::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->get()
            ->map(fn (Crusher $c) => [$c->crusher_code, $c->type, $c->capacity_tph, $c->current_throughput, $c->operating_hours, $c->wear_status, $c->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Crusher Performance Report', [
            ['title' => 'Crushers', 'columns' => ['Code', 'Type', 'Capacity (t/h)', 'Throughput (t/h)', 'Operating Hrs', 'Wear Status', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportDowntimeReport()
    {
        $rows = ProductionDowntime::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('crushing_plant_id')
            ->with('crushingPlant')
            ->get()
            ->map(fn (ProductionDowntime $d) => [$d->crushingPlant?->name, $d->reason, $d->affectedEquipmentLabel() ?? '—', $d->duration_minutes])
            ->all();

        return app(ReportExportService::class)->toPdf('Downtime Report', [
            ['title' => 'Plant Downtime', 'columns' => ['Plant', 'Reason', 'Affected Equipment', 'Duration (min)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportEnergyConsumptionReport()
    {
        $rows = EnergyReading::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('crushingPlant')
            ->latest('date')
            ->get()
            ->map(fn (EnergyReading $e) => [$e->crushingPlant?->name, $e->date->format('d M Y'), $e->electricity_kwh, $e->diesel_litres, $e->energy_cost, $e->costPerTonne() ?? '—'])
            ->all();

        return app(ReportExportService::class)->toPdf('Energy Consumption Report', [
            ['title' => 'Energy Readings', 'columns' => ['Plant', 'Date', 'Electricity (kWh)', 'Diesel (L)', 'Cost', 'Cost/Tonne'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportProductOutputReport()
    {
        $rows = ProductionOutput::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('production_run_id')
            ->with(['productionRun', 'material', 'stockpile'])
            ->get()
            ->map(fn (ProductionOutput $o) => [$o->productionRun?->run_number, $o->material?->name, $o->quantity_produced, $o->stockpile?->label, $o->quality_grade])
            ->all();

        return app(ReportExportService::class)->toPdf('Product Output Report', [
            ['title' => 'Product Output', 'columns' => ['Run #', 'Product', 'Quantity', 'Stockpile', 'Quality Grade'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportOeeReport()
    {
        $svc = app(CrushingPlantAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Availability', 'value' => $svc->availabilityPercent($companyId) . '%'],
            ['label' => 'Performance', 'value' => $svc->performancePercent($companyId) . '%'],
            ['label' => 'Quality', 'value' => $svc->qualityPercent($companyId) . '%'],
            ['label' => 'OEE', 'value' => $svc->oeePercent($companyId) . '%'],
        ];

        return app(ReportExportService::class)->toPdf('OEE Report', [], $kpis, $companyId);
    }

    public function exportExecutiveSummary()
    {
        $svc = app(CrushingPlantAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Active Plants', 'value' => (string) $svc->activePlantsCount($companyId)],
            ['label' => 'Production Today', 'value' => number_format($svc->productionTodayTonnes($companyId), 1) . ' t'],
            ['label' => 'Plant Utilization', 'value' => $svc->plantUtilizationPercent($companyId) . '%'],
            ['label' => 'OEE', 'value' => $svc->oeePercent($companyId) . '%'],
            ['label' => 'Energy Consumption (Today)', 'value' => number_format($svc->energyConsumptionToday($companyId), 0) . ' kWh'],
            ['label' => 'Recovery Rate', 'value' => $svc->recoveryRatePercent($companyId) . '%'],
        ];

        $productRows = ProductionRun::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (ProductionRun $r) => [$r->run_number, $r->input_quantity, $r->output_quantity, $r->yieldPercent() . '%', $r->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Executive Plant Summary', [
            ['title' => 'Recent Production Runs', 'columns' => ['Run #', 'Input (t)', 'Output (t)', 'Yield %', 'Status'], 'rows' => $productRows],
        ], $kpis, $companyId);
    }
}
