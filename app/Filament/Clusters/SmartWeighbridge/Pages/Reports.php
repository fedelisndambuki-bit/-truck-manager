<?php

namespace App\Filament\Clusters\SmartWeighbridge\Pages;

use App\Filament\Clusters\SmartWeighbridge;
use App\Models\DispatchOrder;
use App\Models\LoadingInstruction;
use App\Models\StockMovement;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\WeighbridgeAnalyticsService;
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
        return 'weighbridge.export';
    }

    protected static ?string $cluster = SmartWeighbridge::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 16;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.smart-weighbridge.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Weighbridge Reports',
            'subtitle' => 'Weighbridge register, vehicle movement, dispatch, weight variance, loader productivity, turnaround, queue analysis, customer dispatch, stock movement and executive reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Weighbridge Register', 'description' => 'Every weighing event recorded today.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportWeighbridgeRegister'],
            ['label' => 'Vehicle Movement Report', 'description' => 'Gate-in to exit for every vehicle.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportVehicleMovementReport'],
            ['label' => 'Dispatch Report', 'description' => 'Dispatches over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportDispatchReport'],
            ['label' => 'Weight Variance Report', 'description' => 'Target vs actual weight and variance.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportWeightVarianceReport'],
            ['label' => 'Loader Productivity Report', 'description' => 'Completed loads per loader operator.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportLoaderProductivityReport'],
            ['label' => 'Truck Turnaround Report', 'description' => 'Gate-in to exit duration per truck.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportTruckTurnaroundReport'],
            ['label' => 'Queue Analysis Report', 'description' => 'Current queue by stage.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportQueueAnalysisReport'],
            ['label' => 'Customer Dispatch Report', 'description' => 'Dispatches grouped by customer.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCustomerDispatchReport'],
            ['label' => 'Stock Movement Report', 'description' => 'Dispatch-driven stock movements.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportStockMovementReport'],
            ['label' => 'Executive Weighbridge Report', 'description' => 'KPI snapshot across the whole operation.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveReport'],
        ];
    }

    public function exportWeighbridgeRegister()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereDate('created_at', today())
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->dispatch_number, $o->vehicle_registration, $o->tare_weight, $o->gross_weight, $o->net_weight, $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Weighbridge Register — ' . today()->toFormattedDateString(), [
            ['title' => "Today's Weighings", 'columns' => ['Dispatch #', 'Vehicle', 'Tare (t)', 'Gross (t)', 'Net (t)', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportVehicleMovementReport()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('gate_in_at')
            ->latest('gate_in_at')
            ->limit(100)
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->vehicle_registration, $o->gate_in_at?->format('d M Y H:i'), $o->exit_at?->format('d M Y H:i') ?? '—', $o->turnaroundMinutes() ?? '—', $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Vehicle Movement Report', [
            ['title' => 'Vehicle Movements', 'columns' => ['Vehicle', 'Gate In', 'Gate Out', 'Turnaround (min)', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportDispatchReport()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('created_at', '>=', now()->subDays(30))
            ->with(['customer', 'material'])
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->dispatch_number, $o->customer?->company_name, $o->material?->name, $o->net_weight, $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Dispatch Report (30 Days)', [
            ['title' => 'Dispatches', 'columns' => ['Dispatch #', 'Customer', 'Product', 'Net Weight (t)', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportWeightVarianceReport()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('variance')
            ->latest('gross_weighed_at')
            ->limit(100)
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->dispatch_number, $o->target_net_weight, $o->net_weight, $o->variance, $o->variance_percent . '%', $o->weight_status])
            ->all();

        return app(ReportExportService::class)->toPdf('Weight Variance Report', [
            ['title' => 'Weight Variance', 'columns' => ['Dispatch #', 'Target (t)', 'Actual (t)', 'Variance (t)', 'Variance %', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportLoaderProductivityReport()
    {
        $rows = LoadingInstruction::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Completed')
            ->with('loaderOperator')
            ->get()
            ->groupBy(fn (LoadingInstruction $li) => $li->loaderOperator?->full_name ?? 'Unassigned')
            ->map(fn ($group, $name) => [$name, $group->count(), number_format((float) $group->sum('estimated_quantity_loaded'), 1)])
            ->values()->all();

        return app(ReportExportService::class)->toPdf('Loader Productivity Report', [
            ['title' => 'Loader Productivity', 'columns' => ['Loader', 'Completed Loads', 'Total Quantity (t)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportTruckTurnaroundReport()
    {
        $rows = DispatchOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('exit_at')
            ->latest('exit_at')
            ->limit(100)
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->vehicle_registration, $o->dispatch_number, $o->turnaroundMinutes() . ' min'])
            ->all();

        return app(ReportExportService::class)->toPdf('Truck Turnaround Report', [
            ['title' => 'Turnaround Times', 'columns' => ['Vehicle', 'Dispatch #', 'Turnaround'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportQueueAnalysisReport()
    {
        $svc = app(WeighbridgeAnalyticsService::class);
        $rows = collect($svc->queueAnalysis($this->companyId()))->map(fn ($count, $status) => [$status, $count])->values()->all();

        return app(ReportExportService::class)->toPdf('Queue Analysis Report', [
            ['title' => 'Current Queue by Stage', 'columns' => ['Stage', 'Trucks'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportCustomerDispatchReport()
    {
        $svc = app(WeighbridgeAnalyticsService::class);
        $rows = collect($svc->customerDispatches($this->companyId()))->map(fn ($tonnes, $customer) => [$customer, number_format($tonnes, 1)])->values()->all();

        return app(ReportExportService::class)->toPdf('Customer Dispatch Report (30 Days)', [
            ['title' => 'Dispatches by Customer', 'columns' => ['Customer', 'Tonnes'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportStockMovementReport()
    {
        $rows = StockMovement::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('reference_type', DispatchOrder::class)
            ->where('date', '>=', now()->subDays(30))
            ->with(['material', 'sourceStockpile'])
            ->get()
            ->map(fn (StockMovement $m) => [$m->movement_number, $m->date->format('d M Y'), $m->material?->name, $m->quantity, $m->sourceStockpile?->label])
            ->all();

        return app(ReportExportService::class)->toPdf('Stock Movement Report (Dispatch-Driven, 30 Days)', [
            ['title' => 'Stock Movements', 'columns' => ['Movement #', 'Date', 'Product', 'Quantity (t)', 'Stockpile'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportExecutiveReport()
    {
        $svc = app(WeighbridgeAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => "Today's Tonnes", 'value' => number_format($svc->todaysTonnes($companyId), 1) . ' t'],
            ['label' => "Today's Revenue", 'value' => session('currency', 'KES') . ' ' . number_format($svc->todaysRevenue($companyId), 2)],
            ['label' => 'Avg. Truck Turnaround', 'value' => number_format($svc->averageTruckTurnaroundMinutes($companyId), 0) . ' min'],
            ['label' => 'Weight Accuracy', 'value' => $svc->weightAccuracyPercent($companyId) . '%'],
            ['label' => 'Weighbridge Utilization', 'value' => $svc->weighbridgeUtilizationPercent($companyId) . '%'],
            ['label' => 'Open Exceptions', 'value' => (string) $svc->openExceptionsCount($companyId)],
            ['label' => 'Pending Approvals', 'value' => (string) $svc->pendingApprovalsCount($companyId)],
        ];

        $recentRows = DispatchOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (DispatchOrder $o) => [$o->dispatch_number, $o->vehicle_registration, $o->net_weight, $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Executive Weighbridge Report', [
            ['title' => 'Recent Dispatches', 'columns' => ['Dispatch #', 'Vehicle', 'Net Weight (t)', 'Status'], 'rows' => $recentRows],
        ], $kpis, $companyId);
    }
}
