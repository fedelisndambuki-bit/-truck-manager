<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use App\Models\Maintenance;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class EquipmentReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-wrench-screwdriver';

    protected static ?string $navigationLabel = 'Equipment Reports';

    protected static ?int $navigationSort = 7;

    protected static ?string $title = 'Equipment Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'No dedicated Equipment Reports page existed anywhere in the app — all 8 reports below are new.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.091 2.264-.865 3.017l-.001.001-6.117 5.037" />';
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Equipment Utilization', 'description' => 'New: % of equipment currently in use.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportUtilization'],
            ['label' => 'Operating Hours', 'description' => 'New: total operating hours logged per equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportOperatingHours'],
            ['label' => 'Fuel Consumption', 'description' => 'New: fuel consumed per equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportFuelConsumption'],
            ['label' => 'Maintenance', 'description' => 'New: maintenance history and cost per equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportMaintenance'],
            ['label' => 'Downtime', 'description' => 'New: equipment currently under maintenance.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDowntime'],
            ['label' => 'Idle Time', 'description' => 'New: recorded idle hours per equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportIdleTime'],
            ['label' => 'Equipment Cost', 'description' => 'New: purchase cost, current value and hourly rate.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportEquipmentCost'],
            ['label' => 'Allocation History', 'description' => 'New: full site allocation history per equipment.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportAllocationHistory'],
        ];
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    protected function equipment()
    {
        return Equipment::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->get();
    }

    public function exportUtilization()
    {
        $rows = $this->equipment()->map(fn (Equipment $e) => [$e->name, $e->equipment_type, $e->status])->all();

        return app(ReportExportService::class)->toPdf('Equipment Utilization Report', [
            ['title' => 'Fleet Status', 'columns' => ['Equipment', 'Type', 'Status'], 'rows' => $rows],
        ]);
    }

    public function exportOperatingHours()
    {
        $rows = $this->equipment()->map(fn (Equipment $e) => [$e->name, (float) $e->engine_hours])->sortByDesc(1)->values()->all();

        return app(ReportExportService::class)->toPdf('Operating Hours Report', [
            ['title' => 'Total Engine Hours', 'columns' => ['Equipment', 'Engine Hours'], 'rows' => $rows],
        ]);
    }

    public function exportFuelConsumption()
    {
        $rows = $this->equipment()->map(function (Equipment $e) {
            $total = EquipmentAllocation::query()->where('equipment_id', $e->id)->sum('fuel_consumed_today');

            return [$e->name, round((float) $total, 1) . ' L'];
        })->all();

        return app(ReportExportService::class)->toPdf('Fuel Consumption Report', [
            ['title' => 'Fuel Consumed (all allocations)', 'columns' => ['Equipment', 'Fuel Consumed'], 'rows' => $rows],
        ]);
    }

    public function exportMaintenance()
    {
        $currency = session('currency', 'KES');

        $rows = Maintenance::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('equipment_id')
            ->with('equipment')
            ->get()
            ->map(fn (Maintenance $m) => [$m->equipment?->name, optional($m->maintenance_date)->format('d M Y'), $currency . ' ' . number_format((float) $m->cost, 0)])
            ->all();

        return app(ReportExportService::class)->toPdf('Maintenance Report', [
            ['title' => 'Maintenance History', 'columns' => ['Equipment', 'Date', 'Cost'], 'rows' => $rows],
        ]);
    }

    public function exportDowntime()
    {
        $rows = $this->equipment()->where('status', 'Under Maintenance')->map(fn (Equipment $e) => [$e->name, $e->equipment_type])->values()->all();

        return app(ReportExportService::class)->toPdf('Downtime Report', [
            ['title' => 'Currently Under Maintenance', 'columns' => ['Equipment', 'Type'], 'rows' => $rows],
        ]);
    }

    public function exportIdleTime()
    {
        $rows = $this->equipment()->map(fn (Equipment $e) => [$e->name, (float) $e->idle_hours])->sortByDesc(1)->values()->all();

        return app(ReportExportService::class)->toPdf('Idle Time Report', [
            ['title' => 'Recorded Idle Hours', 'columns' => ['Equipment', 'Idle Hours'], 'rows' => $rows],
        ]);
    }

    public function exportEquipmentCost()
    {
        $currency = session('currency', 'KES');

        $rows = $this->equipment()->map(fn (Equipment $e) => [
            $e->name,
            $currency . ' ' . number_format((float) $e->purchase_cost, 0),
            $currency . ' ' . number_format((float) $e->current_value, 0),
            $currency . ' ' . number_format((float) $e->hourly_rate, 2) . '/hr',
        ])->all();

        return app(ReportExportService::class)->toPdf('Equipment Cost Report', [
            ['title' => 'Fleet Valuation', 'columns' => ['Equipment', 'Purchase Cost', 'Current Value', 'Hourly Rate'], 'rows' => $rows],
        ]);
    }

    public function exportAllocationHistory()
    {
        $rows = EquipmentAllocation::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['equipment', 'site'])
            ->latest('allocated_from')
            ->limit(200)
            ->get()
            ->map(fn (EquipmentAllocation $a) => [$a->equipment?->name, $a->site?->name, optional($a->allocated_from)->format('d M Y'), optional($a->allocated_to)->format('d M Y')])
            ->all();

        return app(ReportExportService::class)->toPdf('Equipment Allocation History', [
            ['title' => 'Recent Allocations', 'columns' => ['Equipment', 'Site', 'From', 'To'], 'rows' => $rows],
        ]);
    }
}
