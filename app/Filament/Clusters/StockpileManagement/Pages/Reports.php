<?php

namespace App\Filament\Clusters\StockpileManagement\Pages;

use App\Filament\Clusters\StockpileManagement;
use App\Models\ProductionOutput;
use App\Models\Stockpile;
use App\Models\StockMovement;
use App\Models\StockpileAdjustment;
use App\Models\StockpilePhysicalCount;
use App\Models\StockReservation;
use App\Services\Construction\ReportExportService;
use App\Services\Quarry\StockpileAnalyticsService;
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
        return 'stockpile.export';
    }

    protected static ?string $cluster = StockpileManagement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.stockpile-management.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Stockpile Reports',
            'subtitle' => 'Current stock, movements, transfers, valuation, reservations, physical counts, variance, aging and executive inventory reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Current Stock Report', 'description' => 'Live quantities across every stockpile.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportCurrentStockReport'],
            ['label' => 'Stock Movement Report', 'description' => 'All movements over the last 30 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportStockMovementReport'],
            ['label' => 'Stock Transfer Report', 'description' => 'Transfers between stockpiles, plants and depots.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportStockTransferReport'],
            ['label' => 'Inventory Valuation Report', 'description' => 'Stock value by product and stockpile.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportInventoryValuationReport'],
            ['label' => 'Reservation Report', 'description' => 'Stock reserved for confirmed customer orders.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportReservationReport'],
            ['label' => 'Physical Count Report', 'description' => 'Recent physical stock counts and their outcomes.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportPhysicalCountReport'],
            ['label' => 'Variance Report', 'description' => 'Physical count variances and root causes.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportVarianceReport'],
            ['label' => 'Stock Aging Report', 'description' => 'How long finished product has sat in stock.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportStockAgingReport'],
            ['label' => 'Executive Inventory Summary', 'description' => 'KPI snapshot across stock, value, movement and accuracy.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveSummary'],
        ];
    }

    public function exportCurrentStockReport()
    {
        $rows = Stockpile::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'extractionSite'])
            ->get()
            ->map(fn (Stockpile $s) => [$s->stockpile_code, $s->label, $s->material?->name, $s->extractionSite?->name, $s->quantity_available, $s->quantity_reserved, $s->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Current Stock Report', [
            ['title' => 'Stockpiles', 'columns' => ['Code', 'Name', 'Product', 'Quarry', 'Available (t)', 'Reserved (t)', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportStockMovementReport()
    {
        $rows = StockMovement::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('date', '>=', now()->subDays(30))
            ->with(['material', 'sourceStockpile', 'destinationStockpile'])
            ->get()
            ->map(fn (StockMovement $m) => [$m->movement_number, $m->date->format('d M Y'), $m->movement_type, $m->material?->name, $m->quantity, $m->sourceStockpile?->label ?? '—', $m->destinationStockpile?->label ?? '—'])
            ->all();

        return app(ReportExportService::class)->toPdf('Stock Movement Report (30 Days)', [
            ['title' => 'Movements', 'columns' => ['Movement #', 'Date', 'Type', 'Product', 'Quantity', 'Source', 'Destination'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportStockTransferReport()
    {
        $rows = \App\Models\ProductionStockTransfer::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'sourceStockpile', 'destinationStockpile'])
            ->get()
            ->map(fn ($t) => [$t->transfer_number, $t->material?->name, $t->quantity, $t->sourceStockpile?->label, $t->destinationStockpile?->label, $t->status, $t->received_status])
            ->all();

        return app(ReportExportService::class)->toPdf('Stock Transfer Report', [
            ['title' => 'Stock Transfers', 'columns' => ['Transfer #', 'Product', 'Quantity', 'From', 'To', 'Status', 'Received'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportInventoryValuationReport()
    {
        $rows = Stockpile::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('material')
            ->get()
            ->map(fn (Stockpile $s) => [$s->stockpile_code, $s->material?->name, $s->quantity_available, $s->unit_value, number_format($s->value(), 2)])
            ->all();

        $svc = app(StockpileAnalyticsService::class);

        return app(ReportExportService::class)->toPdf('Inventory Valuation Report', [
            ['title' => 'Valuation by Stockpile', 'columns' => ['Code', 'Product', 'Quantity (t)', 'Value/Tonne', 'Total Value'], 'rows' => $rows],
        ], [
            ['label' => 'Total Stock Value', 'value' => session('currency', 'KES') . ' ' . number_format($svc->stockValue($this->companyId()), 2)],
        ], $this->companyId());
    }

    public function exportReservationReport()
    {
        $rows = StockReservation::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['stockpile.material', 'customer'])
            ->get()
            ->map(fn (StockReservation $r) => [$r->reservation_number, $r->stockpile?->material?->name, $r->customer?->company_name ?? '—', $r->order_number ?? '—', $r->quantity_reserved, $r->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Reservation Report', [
            ['title' => 'Stock Reservations', 'columns' => ['Reservation #', 'Product', 'Customer', 'Order #', 'Quantity', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportPhysicalCountReport()
    {
        $rows = StockpilePhysicalCount::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('stockpile')
            ->latest('count_date')
            ->get()
            ->map(fn (StockpilePhysicalCount $c) => [$c->count_number, $c->stockpile?->label, $c->system_quantity, $c->physical_quantity, $c->variance(), $c->survey_method, $c->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Physical Count Report', [
            ['title' => 'Physical Stock Counts', 'columns' => ['Count #', 'Stockpile', 'System Qty', 'Physical Qty', 'Variance', 'Method', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportVarianceReport()
    {
        $rows = StockpilePhysicalCount::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Approved')
            ->with('stockpile')
            ->get()
            ->map(fn (StockpilePhysicalCount $c) => [$c->count_number, $c->stockpile?->label, $c->variance(), $c->variancePercent() . '%'])
            ->all();

        $adjustmentRows = StockpileAdjustment::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('status', 'Approved')
            ->with('stockpile')
            ->get()
            ->map(fn (StockpileAdjustment $a) => [$a->adjustment_number, $a->stockpile?->label, $a->quantity, $a->reason])
            ->all();

        return app(ReportExportService::class)->toPdf('Variance Report', [
            ['title' => 'Physical Count Variances', 'columns' => ['Count #', 'Stockpile', 'Variance (t)', 'Variance %'], 'rows' => $rows],
            ['title' => 'Approved Adjustments', 'columns' => ['Adjustment #', 'Stockpile', 'Quantity (t)', 'Reason'], 'rows' => $adjustmentRows],
        ], [], $this->companyId());
    }

    public function exportStockAgingReport()
    {
        $svc = app(StockpileAnalyticsService::class);
        $buckets = $svc->stockAgingBuckets($this->companyId());

        $rows = collect($buckets)->map(fn ($qty, $bucket) => [$bucket, number_format($qty, 1)])->values()->all();

        return app(ReportExportService::class)->toPdf('Stock Aging Report', [
            ['title' => 'Stock Age Distribution', 'columns' => ['Age Bucket', 'Quantity (t)'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportExecutiveSummary()
    {
        $svc = app(StockpileAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Total Stock Available', 'value' => number_format($svc->totalStockAvailable($companyId), 1) . ' t'],
            ['label' => 'Stock Value', 'value' => session('currency', 'KES') . ' ' . number_format($svc->stockValue($companyId), 2)],
            ['label' => 'Reserved Stock', 'value' => number_format($svc->reservedStock($companyId), 1) . ' t'],
            ['label' => 'Low Stock Alerts', 'value' => (string) $svc->lowStockCount($companyId)],
            ['label' => 'Critical Stock Alerts', 'value' => (string) $svc->criticalStockCount($companyId)],
            ['label' => 'Inventory Accuracy', 'value' => $svc->inventoryAccuracyPercent($companyId) . '%'],
            ['label' => 'Inventory Turnover Ratio', 'value' => (string) $svc->inventoryTurnoverRatio($companyId)],
        ];

        $productRows = ProductionOutput::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('stockpile_id')
            ->latest('created_at')
            ->limit(10)
            ->with(['material', 'stockpile'])
            ->get()
            ->map(fn (ProductionOutput $o) => [$o->material?->name, $o->quantity_produced, $o->stockpile?->label, $o->created_at->format('d M Y')])
            ->all();

        return app(ReportExportService::class)->toPdf('Executive Inventory Summary', [
            ['title' => 'Recent Production Received', 'columns' => ['Product', 'Quantity (t)', 'Stockpile', 'Date'], 'rows' => $productRows],
        ], $kpis, $companyId);
    }
}
