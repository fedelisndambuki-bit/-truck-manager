<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Illuminate\Support\Facades\Auth;
use Filament\Pages\Page;

class MaterialsReports extends Page
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

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Materials Reports';

    protected static ?int $navigationSort = 8;

    protected static ?string $title = 'Materials Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Inventory, Stock Valuation, Consumption, Purchase, Supplier, Waste, Material Cost, Delivery, GRN, Stock Movement, Material Allocation, Stock Adjustment and Project Material Cost reports are already available in Materials Management, plus 2 new reports.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-9-5.25L3 7.5m18 0l-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9" />';
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Supplier Performance', 'description' => 'New: delivery, quality and overall performance ratings per supplier.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSupplierPerformance'],
            ['label' => 'Warehouse Performance', 'description' => 'New: stock value and delivery throughput per warehouse.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportWarehousePerformance'],
            ['label' => 'Open Materials Management Reports', 'description' => 'Inventory, Stock Valuation, Consumption, Purchase, Supplier, Waste, Material Cost, Delivery, GRN, Stock Movement, Material Allocation, Stock Adjustment and Project Material Cost.', 'icon' => 'heroicon-o-arrow-top-right-on-square', 'url' => \App\Filament\Pages\MaterialsReports::getUrl()],
        ];
    }

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function exportSupplierPerformance()
    {
        $rows = Supplier::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->orderByDesc('performance_rating')
            ->get()
            ->map(fn (Supplier $s) => [
                $s->company_name,
                $s->performance_rating !== null ? number_format((float) $s->performance_rating, 1) . ' / 5' : '—',
                $s->delivery_performance_rating !== null ? number_format((float) $s->delivery_performance_rating, 1) . ' / 5' : '—',
                $s->quality_rating !== null ? number_format((float) $s->quality_rating, 1) . ' / 5' : '—',
            ])
            ->all();

        return app(ReportExportService::class)->toPdf('Supplier Performance Report', [
            ['title' => 'Supplier Ratings', 'columns' => ['Supplier', 'Overall', 'Delivery', 'Quality'], 'rows' => $rows],
        ]);
    }

    public function exportWarehousePerformance()
    {
        $currency = session('currency', 'KES');

        $rows = Warehouse::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->withCount('deliveries')
            ->get()
            ->map(fn (Warehouse $w) => [$w->name, $currency . ' ' . number_format($w->current_stock_value, 0), $w->deliveries_count])
            ->all();

        return app(ReportExportService::class)->toPdf('Warehouse Performance Report', [
            ['title' => 'Warehouse Summary', 'columns' => ['Warehouse', 'Stock Value', 'Deliveries'], 'rows' => $rows],
        ]);
    }
}
