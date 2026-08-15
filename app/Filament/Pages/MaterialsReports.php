<?php

namespace App\Filament\Pages;

use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\MaterialDelivery;
use App\Models\MaterialWastage;
use App\Models\PurchaseOrder;
use App\Models\StockAdjustment;
use App\Models\StockTransfer;
use App\Models\Supplier;
use App\Traits\HasIndustryAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class MaterialsReports extends Page
{
    use HasIndustryAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 19;

    protected static ?string $title = 'Materials Reports';

    protected static string $view = 'filament.pages.materials-reports';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('inventory_report')
                ->label('Inventory Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generateInventoryReport'),
            Action::make('stock_valuation_report')
                ->label('Stock Valuation')
                ->icon('heroicon-o-document-arrow-down')
                ->color('success')
                ->action('generateStockValuationReport'),
            Action::make('consumption_report')
                ->label('Consumption Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('warning')
                ->action('generateConsumptionReport'),
            Action::make('purchase_report')
                ->label('Purchase Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('info')
                ->action('generatePurchaseReport'),
            Action::make('supplier_report')
                ->label('Supplier Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateSupplierReport'),
            Action::make('waste_report')
                ->label('Waste Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action('generateWasteReport'),
            Action::make('material_cost_report')
                ->label('Material Cost Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateMaterialCostReport'),
            Action::make('delivery_report')
                ->label('Delivery Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateDeliveryReport'),
            Action::make('grn_report')
                ->label('GRN Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateGrnReport'),
            Action::make('stock_movement_report')
                ->label('Stock Movement Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateStockMovementReport'),
            Action::make('material_allocation_report')
                ->label('Material Allocation Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateMaterialAllocationReport'),
            Action::make('stock_adjustment_report')
                ->label('Stock Adjustment Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateStockAdjustmentReport'),
            Action::make('project_material_cost_report')
                ->label('Project Material Cost Report')
                ->icon('heroicon-o-document-arrow-down')
                ->color('gray')
                ->action('generateProjectMaterialCostReport'),
        ];
    }

    private function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function generateInventoryReport()
    {
        $materials = Material::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['category', 'preferredSupplier'])
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('reports.materials-inventory', [
            'title' => 'Inventory Report',
            'date' => now()->format('d M Y'),
            'materials' => $materials,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'inventory-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateStockValuationReport()
    {
        $materials = Material::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->orderByDesc('quantity_on_hand')
            ->get();

        $pdf = Pdf::loadView('reports.materials-stock-valuation', [
            'title' => 'Stock Valuation Report',
            'date' => now()->format('d M Y'),
            'materials' => $materials,
            'currency' => session('currency', 'KES'),
            'totalValue' => $materials->sum(fn (Material $m) => $m->inventory_value),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'stock-valuation-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateConsumptionReport()
    {
        $consumptions = MaterialConsumption::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'project', 'site'])
            ->latest('date')
            ->get();

        $pdf = Pdf::loadView('reports.materials-consumption', [
            'title' => 'Material Consumption Report',
            'date' => now()->format('d M Y'),
            'consumptions' => $consumptions,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'consumption-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generatePurchaseReport()
    {
        $orders = PurchaseOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('supplier')
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.materials-purchase', [
            'title' => 'Purchase Report',
            'date' => now()->format('d M Y'),
            'orders' => $orders,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'purchase-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateSupplierReport()
    {
        $suppliers = Supplier::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->withCount('purchaseOrders')
            ->orderBy('company_name')
            ->get();

        $pdf = Pdf::loadView('reports.materials-supplier', [
            'title' => 'Supplier Report',
            'date' => now()->format('d M Y'),
            'suppliers' => $suppliers,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'supplier-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateWasteReport()
    {
        $wastages = MaterialWastage::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'project'])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.materials-waste', [
            'title' => 'Waste Report',
            'date' => now()->format('d M Y'),
            'wastages' => $wastages,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'waste-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateMaterialCostReport()
    {
        $materials = Material::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->orderBy('name')
            ->get();

        $pdf = Pdf::loadView('reports.materials-cost', [
            'title' => 'Material Cost Report',
            'date' => now()->format('d M Y'),
            'materials' => $materials,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'material-cost-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateDeliveryReport()
    {
        $deliveries = MaterialDelivery::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'supplier', 'site', 'goodsReceivedNote'])
            ->latest('delivered_at')
            ->get();

        $pdf = Pdf::loadView('reports.materials-delivery', [
            'title' => 'Delivery Report',
            'date' => now()->format('d M Y'),
            'deliveries' => $deliveries,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'delivery-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateGrnReport()
    {
        $grns = \App\Models\GoodsReceivedNote::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('materialDelivery.material')
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.materials-grn', [
            'title' => 'GRN Report',
            'date' => now()->format('d M Y'),
            'grns' => $grns,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'grn-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateStockMovementReport()
    {
        $transfers = StockTransfer::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'sourceWarehouse', 'sourceProject', 'sourceSite', 'destinationWarehouse', 'destinationProject', 'destinationSite'])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.materials-stock-movement', [
            'title' => 'Stock Movement Report',
            'date' => now()->format('d M Y'),
            'transfers' => $transfers,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'stock-movement-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateMaterialAllocationReport()
    {
        $allocations = \App\Models\MaterialAllocation::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'project', 'site'])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.materials-allocation', [
            'title' => 'Material Allocation Report',
            'date' => now()->format('d M Y'),
            'allocations' => $allocations,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'material-allocation-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateStockAdjustmentReport()
    {
        $adjustments = StockAdjustment::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['material', 'warehouse', 'approvedBy'])
            ->latest('created_at')
            ->get();

        $pdf = Pdf::loadView('reports.materials-stock-adjustment', [
            'title' => 'Stock Adjustment Report',
            'date' => now()->format('d M Y'),
            'adjustments' => $adjustments,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'stock-adjustment-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function generateProjectMaterialCostReport()
    {
        $companyId = $this->companyId();

        $projects = \App\Models\Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereHas('materialAllocations')
            ->with(['materialAllocations.material'])
            ->get();

        $pdf = Pdf::loadView('reports.materials-project-cost', [
            'title' => 'Project Material Cost Report',
            'date' => now()->format('d M Y'),
            'projects' => $projects,
            'currency' => session('currency', 'KES'),
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, 'project-material-cost-report-' . now()->format('Y-m-d') . '.pdf');
    }

    public function getReportCards(): array
    {
        $companyId = $this->companyId();

        return [
            [
                'title' => 'Inventory Report',
                'description' => 'Full material master list with stock status.',
                'icon' => 'heroicon-o-cube',
                'count' => Material::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' materials',
            ],
            [
                'title' => 'Stock Valuation',
                'description' => 'Inventory value by material at current cost.',
                'icon' => 'heroicon-o-banknotes',
                'count' => session('currency', 'KES') . ' ' . number_format(
                    Material::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get()->sum(fn (Material $m) => $m->inventory_value),
                    2
                ),
            ],
            [
                'title' => 'Material Consumption Report',
                'description' => 'Daily usage across all projects and sites.',
                'icon' => 'heroicon-o-fire',
                'count' => MaterialConsumption::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' entries',
            ],
            [
                'title' => 'Material Cost Report',
                'description' => 'Cost comparison by material at current rates.',
                'icon' => 'heroicon-o-currency-dollar',
                'count' => Material::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' materials tracked',
            ],
            [
                'title' => 'Purchase Report',
                'description' => 'Purchase orders and spend by supplier.',
                'icon' => 'heroicon-o-document-check',
                'count' => PurchaseOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' orders',
            ],
            [
                'title' => 'Supplier Report',
                'description' => 'Supplier performance and delivery ratings.',
                'icon' => 'heroicon-o-truck',
                'count' => Supplier::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' suppliers',
            ],
            [
                'title' => 'Delivery Report',
                'description' => 'Incoming deliveries and inspection status.',
                'icon' => 'heroicon-o-truck',
                'count' => MaterialDelivery::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' deliveries',
            ],
            [
                'title' => 'GRN Report',
                'description' => 'Goods received notes and acceptance rates.',
                'icon' => 'heroicon-o-clipboard-document-check',
                'count' => \App\Models\GoodsReceivedNote::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' GRNs',
            ],
            [
                'title' => 'Stock Movement Report',
                'description' => 'Transfers between warehouses, projects, and sites.',
                'icon' => 'heroicon-o-arrows-right-left',
                'count' => StockTransfer::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' transfers',
            ],
            [
                'title' => 'Material Allocation Report',
                'description' => 'Allocated vs consumed by project.',
                'icon' => 'heroicon-o-squares-plus',
                'count' => \App\Models\MaterialAllocation::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' allocations',
            ],
            [
                'title' => 'Waste Report',
                'description' => 'Damaged, lost, expired, and overuse wastage.',
                'icon' => 'heroicon-o-trash',
                'count' => MaterialWastage::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' records',
            ],
            [
                'title' => 'Stock Adjustment Report',
                'description' => 'Manual corrections and physical count reconciliations.',
                'icon' => 'heroicon-o-adjustments-horizontal',
                'count' => StockAdjustment::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count() . ' adjustments',
            ],
            [
                'title' => 'Project Material Cost Report',
                'description' => 'Material cost breakdown by project.',
                'icon' => 'heroicon-o-building-office-2',
                'count' => \App\Models\MaterialAllocation::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->distinct('project_id')->count('project_id') . ' projects',
            ],
        ];
    }
}
