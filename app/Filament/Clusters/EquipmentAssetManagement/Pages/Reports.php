<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Pages;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Models\Breakdown;
use App\Models\Equipment;
use App\Models\EquipmentCostEntry;
use App\Models\EquipmentUtilizationLog;
use App\Models\FuelLog;
use App\Models\Maintenance;
use App\Models\MaintenanceWorkOrder;
use App\Models\Part;
use App\Models\PreventiveMaintenancePlan;
use App\Models\Tyre;
use App\Models\WarrantyClaim;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\EamAnalyticsService;
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
        return 'eam.export';
    }

    protected static ?string $cluster = EquipmentAssetManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 24;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.equipment-asset-management.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Equipment & Asset Management Reports',
            'subtitle' => 'Register, maintenance, work order, breakdown, downtime, utilization, fuel, tyre, parts, warranty, cost and executive reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Equipment Register', 'description' => 'Full asset register with classification and status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportEquipmentRegister'],
            ['label' => 'Asset Register', 'description' => 'Financial view — cost, book value, useful life.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportAssetRegister'],
            ['label' => 'Maintenance Schedule', 'description' => 'Active preventive maintenance plans and next due.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportMaintenanceSchedule'],
            ['label' => 'Work Order Report', 'description' => 'All work orders in the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportWorkOrderReport'],
            ['label' => 'Breakdown Report', 'description' => 'Breakdown incidents in the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportBreakdownReport'],
            ['label' => 'Downtime Report', 'description' => 'Service history downtime hours by equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDowntimeReport'],
            ['label' => 'Utilization Report', 'description' => 'Utilization/availability logs, last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportUtilizationReport'],
            ['label' => 'Fuel Report', 'description' => 'Fuel transactions and cost by equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportFuelReport'],
            ['label' => 'Tyre Report', 'description' => 'Tyre fleet status, tread and cost per hour.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportTyreReport'],
            ['label' => 'Spare Parts Report', 'description' => 'Stock levels and reorder status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSparePartsReport'],
            ['label' => 'Warranty Report', 'description' => 'Warranty claims and recovery status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportWarrantyReport'],
            ['label' => 'Cost Analysis', 'description' => 'Cost by category, last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCostAnalysisReport'],
            ['label' => 'MTBF Report', 'description' => 'Mean time between failures, fleet-wide.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportMtbfReport'],
            ['label' => 'MTTR Report', 'description' => 'Mean time to repair, fleet-wide.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportMttrReport'],
            ['label' => 'OEE Report', 'description' => 'Availability, utilization and OEE.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportOeeReport'],
            ['label' => 'Executive Equipment Report', 'description' => 'Full KPI snapshot across the fleet.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveReport'],
        ];
    }

    public function exportEquipmentRegister()
    {
        $rows = Equipment::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('category')
            ->get()
            ->map(fn (Equipment $e) => [$e->equipment_code, $e->name, $e->category?->name, $e->manufacturer, $e->model, $e->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Equipment Register', [
            ['title' => 'Equipment', 'columns' => ['Code', 'Name', 'Category', 'Manufacturer', 'Model', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportAssetRegister()
    {
        $rows = Equipment::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->get()
            ->map(fn (Equipment $e) => [$e->equipment_code, $e->name, $e->purchase_cost, $e->current_value, $e->useful_life_years, $e->residual_value])
            ->all();

        return app(ReportExportService::class)->toPdf('Asset Register', [
            ['title' => 'Assets', 'columns' => ['Code', 'Name', 'Purchase Cost', 'Book Value', 'Useful Life (yrs)', 'Residual Value'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportMaintenanceSchedule()
    {
        $rows = PreventiveMaintenancePlan::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Active')
            ->with('equipment')
            ->get()
            ->map(fn (PreventiveMaintenancePlan $p) => [$p->equipment?->name, $p->name, $p->trigger_type, $p->interval_value, $p->next_due_value ?? '—'])
            ->all();

        return app(ReportExportService::class)->toPdf('Maintenance Schedule', [
            ['title' => 'Active Preventive Maintenance Plans', 'columns' => ['Equipment', 'Plan', 'Trigger', 'Interval', 'Next Due'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportWorkOrderReport()
    {
        $rows = MaintenanceWorkOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['equipment', 'technician'])
            ->get()
            ->map(fn (MaintenanceWorkOrder $o) => [$o->work_order_number, $o->equipment?->name, $o->work_order_type, $o->priority, $o->technician?->full_name, $o->status, $o->total_cost ?? '—'])
            ->all();

        return app(ReportExportService::class)->toPdf('Work Order Report (30 Days)', [
            ['title' => 'Work Orders', 'columns' => ['WO#', 'Equipment', 'Type', 'Priority', 'Technician', 'Status', 'Total Cost'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportBreakdownReport()
    {
        $rows = Breakdown::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('occurred_at', '>=', now()->subDays(30))
            ->with('equipment')
            ->get()
            ->map(fn (Breakdown $b) => [$b->breakdown_number, $b->equipment?->name, $b->occurred_at->format('d M Y H:i'), $b->severity, $b->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Breakdown Report (30 Days)', [
            ['title' => 'Breakdowns', 'columns' => ['Ref#', 'Equipment', 'Occurred', 'Severity', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportDowntimeReport()
    {
        $rows = Maintenance::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('equipment_id')
            ->whereNotNull('downtime_hours')
            ->with('equipment')
            ->get()
            ->groupBy('equipment_id')
            ->map(fn ($group) => [$group->first()->equipment?->name, $group->count(), number_format((float) $group->sum('downtime_hours'), 1)])
            ->values()->all();

        return app(ReportExportService::class)->toPdf('Downtime Report', [
            ['title' => 'Downtime by Equipment', 'columns' => ['Equipment', 'Service Events', 'Total Downtime (hrs)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportUtilizationReport()
    {
        $rows = EquipmentUtilizationLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('log_date', '>=', now()->subDays(30))
            ->with('equipment')
            ->get()
            ->map(fn (EquipmentUtilizationLog $l) => [$l->equipment?->name, $l->log_date->format('d M Y'), $l->operating_hours, $l->availability_percent . '%', $l->utilization_percent . '%'])
            ->all();

        return app(ReportExportService::class)->toPdf('Utilization Report (30 Days)', [
            ['title' => 'Utilization Logs', 'columns' => ['Equipment', 'Date', 'Operating Hrs', 'Availability', 'Utilization'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportFuelReport()
    {
        $rows = FuelLog::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('equipment_id')
            ->where('date', '>=', now()->subDays(30))
            ->with('equipment')
            ->get()
            ->map(fn (FuelLog $f) => [$f->equipment?->name, $f->date->format('d M Y'), $f->quantity_litres, $f->cost_per_litre, $f->total_cost])
            ->all();

        return app(ReportExportService::class)->toPdf('Fuel Report (30 Days)', [
            ['title' => 'Fuel Transactions', 'columns' => ['Equipment', 'Date', 'Litres', 'Cost/L', 'Total Cost'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportTyreReport()
    {
        $rows = Tyre::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('equipment')
            ->get()
            ->map(fn (Tyre $t) => [$t->serial_number, $t->equipment?->name, $t->position, $t->tread_depth, $t->cost_per_hour, $t->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Tyre Report', [
            ['title' => 'Tyre Fleet', 'columns' => ['Tyre No.', 'Equipment', 'Position', 'Tread Depth', 'Cost/Hr', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportSparePartsReport()
    {
        $rows = Part::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->get()
            ->map(fn (Part $p) => [$p->part_number, $p->name, $p->stock_quantity, $p->reorder_level, $p->isBelowReorderLevel() ? 'Reorder' : 'OK'])
            ->all();

        return app(ReportExportService::class)->toPdf('Spare Parts Report', [
            ['title' => 'Spare Parts Stock', 'columns' => ['Part #', 'Name', 'In Stock', 'Reorder Level', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportWarrantyReport()
    {
        $rows = WarrantyClaim::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('equipment')
            ->get()
            ->map(fn (WarrantyClaim $w) => [$w->claim_number, $w->equipment?->name, $w->covered_component, $w->filed_at->format('d M Y'), $w->status, $w->cost_recovered ?? '—'])
            ->all();

        return app(ReportExportService::class)->toPdf('Warranty Report', [
            ['title' => 'Warranty Claims', 'columns' => ['Claim #', 'Equipment', 'Component', 'Filed', 'Status', 'Cost Recovered'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportCostAnalysisReport()
    {
        $rows = EquipmentCostEntry::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('incurred_on', '>=', now()->subDays(30))
            ->get()
            ->groupBy('cost_category')
            ->map(fn ($group, $category) => [$category, $group->count(), number_format((float) $group->sum('amount'), 2)])
            ->values()->all();

        return app(ReportExportService::class)->toPdf('Cost Analysis (30 Days)', [
            ['title' => 'Cost by Category', 'columns' => ['Category', 'Entries', 'Total Cost'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportMtbfReport()
    {
        $svc = app(EamAnalyticsService::class);

        return app(ReportExportService::class)->toPdf('MTBF Report', [], [
            ['label' => 'Mean Time Between Failures', 'value' => number_format($svc->mtbfHours($this->companyId()), 1) . ' hrs'],
        ], $this->companyId());
    }

    public function exportMttrReport()
    {
        $svc = app(EamAnalyticsService::class);

        return app(ReportExportService::class)->toPdf('MTTR Report', [], [
            ['label' => 'Mean Time To Repair', 'value' => number_format($svc->mttrHours($this->companyId()), 1) . ' hrs'],
            ['label' => 'Average Repair Time (Logged Hours)', 'value' => number_format($svc->averageRepairTimeHours($this->companyId()), 1) . ' hrs'],
        ], $this->companyId());
    }

    public function exportOeeReport()
    {
        $svc = app(EamAnalyticsService::class);
        $companyId = $this->companyId();

        return app(ReportExportService::class)->toPdf('OEE Report', [], [
            ['label' => 'Availability', 'value' => $svc->availabilityPercent($companyId) . '%'],
            ['label' => 'Utilization', 'value' => $svc->utilizationPercentToday($companyId) . '%'],
            ['label' => 'OEE (Availability x Utilization)', 'value' => $svc->oeePercent($companyId) . '%'],
        ], $companyId);
    }

    public function exportExecutiveReport()
    {
        $svc = app(EamAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Total Equipment', 'value' => (string) $svc->totalEquipmentCount($companyId)],
            ['label' => 'Equipment Availability', 'value' => $svc->availabilityPercent($companyId) . '%'],
            ['label' => 'Equipment Utilization', 'value' => $svc->utilizationPercentToday($companyId) . '%'],
            ['label' => 'Equipment OEE', 'value' => $svc->oeePercent($companyId) . '%'],
            ['label' => 'Open Work Orders', 'value' => (string) $svc->openWorkOrdersCount($companyId)],
            ['label' => 'Overdue Maintenance', 'value' => (string) $svc->overdueMaintenanceCount($companyId)],
            ['label' => 'MTBF', 'value' => number_format($svc->mtbfHours($companyId), 1) . ' hrs'],
            ['label' => 'MTTR', 'value' => number_format($svc->mttrHours($companyId), 1) . ' hrs'],
            ['label' => 'Cost Per Hour (30d)', 'value' => number_format($svc->costPerHour($companyId), 2) . ' ' . session('currency', 'KES')],
            ['label' => 'Cost Per Tonne (30d)', 'value' => number_format($svc->costPerTonne($companyId), 2) . ' ' . session('currency', 'KES')],
        ];

        return app(ReportExportService::class)->toPdf('Executive Equipment Report', [], $kpis, $companyId);
    }
}
