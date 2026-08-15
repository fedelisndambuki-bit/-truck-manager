<?php

namespace App\Filament\Clusters\ProductionManagement\Pages;

use App\Filament\Clusters\ProductionManagement;
use App\Models\ProductionLog;
use App\Models\ProductionTarget;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\QuarryAnalyticsService;
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
        return 'production.export';
    }

    protected static ?string $cluster = ProductionManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.production-management.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Production Reports',
            'subtitle' => 'Daily, shift, plant, product, yield, downtime, operator and executive production reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Daily Production Report', 'description' => 'Production logged today across every quarry.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDailyProductionReport'],
            ['label' => 'Shift Report', 'description' => 'Production totals by shift over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportShiftReport'],
            ['label' => 'Plant Report', 'description' => 'Crushing plant output and downtime over the last 14 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportPlantReport'],
            ['label' => 'Product Report', 'description' => 'Product mix — quantity produced by product over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportProductReport'],
            ['label' => 'Yield Report', 'description' => 'Feed, finished quantity, waste and yield % by production record.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportYieldReport'],
            ['label' => 'Downtime Report', 'description' => 'Downtime events grouped by reason over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDowntimeReport'],
            ['label' => 'Operator Performance', 'description' => 'Production totals by operator over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportOperatorPerformanceReport'],
            ['label' => 'Production Target Report', 'description' => 'Target vs actual and achievement % for every target.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportProductionTargetReport'],
            ['label' => 'Executive Production Summary', 'description' => 'KPI snapshot across production, yield, plant and downtime.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveSummary'],
        ];
    }

    public function exportDailyProductionReport()
    {
        $rows = ProductionLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereDate('date', today())
            ->with(['extractionSite', 'shift'])
            ->get()
            ->map(fn (ProductionLog $l) => [
                $l->production_number, $l->extractionSite?->name, $l->material_type,
                number_format((float) $l->quantity_tonnes, 1), $l->shift, $l->status,
            ])->all();

        return app(ReportExportService::class)->toPdf('Daily Production Report', [
            ['title' => "Production — {$this->companyId()} — " . today()->toFormattedDateString(), 'columns' => ['Production #', 'Quarry', 'Material', 'Quantity (t)', 'Shift', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportShiftReport()
    {
        $rows = ProductionLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('date', '>=', now()->subDays(30))
            ->get()
            ->groupBy('shift')
            ->map(fn ($group, $shift) => [$shift, $group->count(), number_format((float) $group->sum('quantity_tonnes'), 1)])
            ->values()->all();

        return app(ReportExportService::class)->toPdf('Shift Report (30 Days)', [
            ['title' => 'Production by Shift', 'columns' => ['Shift', 'Records', 'Total Tonnes'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportPlantReport()
    {
        $qa = app(QuarryAnalyticsService::class);
        $rows = \App\Models\CrushingPlant::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->withSum(['logs' => fn ($q) => $q->where('date', '>=', now()->subDays(14))], 'output_tonnes')
            ->withSum(['logs' => fn ($q) => $q->where('date', '>=', now()->subDays(14))], 'downtime_minutes')
            ->get()
            ->map(fn ($p) => [$p->name, $p->status, number_format((float) $p->logs_sum_output_tonnes, 1), (int) $p->logs_sum_downtime_minutes])
            ->all();

        return app(ReportExportService::class)->toPdf('Plant Report (14 Days)', [
            ['title' => 'Crushing Plant Output', 'columns' => ['Plant', 'Status', 'Output (t)', 'Downtime (min)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportProductReport()
    {
        $mix = app(QuarryAnalyticsService::class)->productMix($this->companyId());
        $rows = collect($mix)->map(fn ($qty, $material) => [$material, number_format($qty, 1)])->values()->all();

        return app(ReportExportService::class)->toPdf('Product Report (30 Days)', [
            ['title' => 'Product Mix', 'columns' => ['Product', 'Quantity Produced (t)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportYieldReport()
    {
        $rows = ProductionLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Approved')
            ->whereNotNull('yield_percent')
            ->where('date', '>=', now()->subDays(30))
            ->get()
            ->map(fn (ProductionLog $l) => [
                $l->production_number, $l->date->format('d M Y'),
                number_format((float) $l->feed_quantity, 1), number_format((float) $l->finished_product_quantity, 1),
                number_format((float) $l->waste_material, 1), number_format((float) $l->yield_percent, 1) . '%',
            ])->all();

        return app(ReportExportService::class)->toPdf('Yield Report (30 Days)', [
            ['title' => 'Production Yield', 'columns' => ['Production #', 'Date', 'Feed (t)', 'Finished (t)', 'Waste (t)', 'Yield %'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportDowntimeReport()
    {
        $byReason = app(QuarryAnalyticsService::class)->downtimeByReason($this->companyId());
        $rows = collect($byReason)->map(fn ($minutes, $reason) => [$reason, $minutes, round($minutes / 60, 1)])->values()->all();

        return app(ReportExportService::class)->toPdf('Downtime Report (30 Days)', [
            ['title' => 'Downtime by Reason', 'columns' => ['Reason', 'Minutes', 'Hours'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportOperatorPerformanceReport()
    {
        $rows = ProductionLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('date', '>=', now()->subDays(30))
            ->whereNotNull('operator_id')
            ->with('operator')
            ->get()
            ->groupBy(fn (ProductionLog $l) => $l->operator?->full_name ?? 'Unassigned')
            ->map(fn ($group, $name) => [$name, $group->count(), number_format((float) $group->sum('quantity_tonnes'), 1)])
            ->values()->all();

        return app(ReportExportService::class)->toPdf('Operator Performance Report (30 Days)', [
            ['title' => 'Production by Operator', 'columns' => ['Operator', 'Records', 'Total Tonnes'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportProductionTargetReport()
    {
        $rows = ProductionTarget::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('extractionSite')
            ->latest('period_start')
            ->get()
            ->map(fn (ProductionTarget $t) => [
                $t->extractionSite?->name, $t->period_type, number_format((float) $t->target_quantity, 1),
                number_format($t->actualQuantity(), 1), $t->achievementPercent() . '%',
            ])->all();

        return app(ReportExportService::class)->toPdf('Production Target Report', [
            ['title' => 'Target vs Actual', 'columns' => ['Quarry', 'Period', 'Target (t)', 'Actual (t)', 'Achievement %'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportExecutiveSummary()
    {
        $qa = app(QuarryAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Production Today', 'value' => number_format($qa->productionToday($companyId), 1) . ' t'],
            ['label' => 'Production This Month', 'value' => number_format($qa->productionMonth($companyId), 1) . ' t'],
            ['label' => 'Target Achievement', 'value' => $qa->targetAchievementPercent($companyId) . '%'],
            ['label' => 'Yield %', 'value' => $qa->yieldPercent($companyId) . '%'],
            ['label' => 'Waste %', 'value' => $qa->wastePercent($companyId) . '%'],
            ['label' => 'Plant Utilization', 'value' => $qa->plantUtilizationPercent($companyId) . '%'],
            ['label' => 'Downtime Hours (Today)', 'value' => number_format($qa->downtimeHoursToday($companyId), 1) . ' hrs'],
        ];

        $productRows = collect($qa->productMix($companyId))->map(fn ($qty, $material) => [$material, number_format($qty, 1)])->values()->all();

        return app(ReportExportService::class)->toPdf('Executive Production Summary', [
            ['title' => 'Product Mix (30 Days)', 'columns' => ['Product', 'Quantity (t)'], 'rows' => $productRows],
        ], $kpis, $companyId);
    }
}
