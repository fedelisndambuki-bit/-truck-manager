<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Models\GoodsReceivedNote;
use App\Models\ProcurementBudget;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\PurchaseReturn;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Services\BudgetValidationService;
use App\Services\Construction\ReportExportService;
use App\Services\ProcurementAnalyticsService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class Reports extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.export';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 19;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.procurement.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />',
            'title' => 'Procurement Reports',
            'subtitle' => 'Purchase register, orders, requisitions, suppliers, contracts, receiving, returns, spend and budget reporting.',
        ]);
    }

    public function getHubCards(): array
    {
        return [
            ['label' => 'Purchase Register', 'description' => 'All purchase orders in the last 90 days.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportPurchaseRegister'],
            ['label' => 'Purchase Order Report', 'description' => 'Purchase orders by status and supplier.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportPurchaseOrderReport'],
            ['label' => 'Requisition Report', 'description' => 'Purchase requisitions and their status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportRequisitionReport'],
            ['label' => 'Supplier Report', 'description' => 'Supplier profiles, ratings and status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSupplierReport'],
            ['label' => 'Supplier Performance Report', 'description' => 'Ratings and risk across all suppliers.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSupplierPerformanceReport'],
            ['label' => 'Contract Register', 'description' => 'All supplier contracts and framework agreements.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportContractRegister'],
            ['label' => 'Goods Receiving Report', 'description' => 'GRNs — accepted, rejected and damaged quantities.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportGoodsReceivingReport'],
            ['label' => 'Return Report', 'description' => 'Purchase returns by reason and status.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportReturnReport'],
            ['label' => 'Spend Analysis Report', 'description' => 'Spend by department, supplier and category.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportSpendAnalysisReport'],
            ['label' => 'Budget Utilization Report', 'description' => 'Approved budget vs. committed spend.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportBudgetUtilizationReport'],
            ['label' => 'Executive Procurement Dashboard', 'description' => 'Full KPI snapshot across the procurement lifecycle.', 'icon' => 'heroicon-o-document-arrow-down', 'action' => 'exportExecutiveReport'],
        ];
    }

    public function exportPurchaseRegister()
    {
        $rows = PurchaseOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('created_at', '>=', now()->subDays(90))
            ->with('supplier')
            ->get()
            ->map(fn (PurchaseOrder $o) => [$o->po_number, $o->supplier?->company_name, $o->currency, number_format((float) $o->total_amount, 2), $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Purchase Register (90 Days)', [
            ['title' => 'Purchase Orders', 'columns' => ['PO #', 'Supplier', 'Currency', 'Total', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportPurchaseOrderReport()
    {
        $rows = PurchaseOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['supplier', 'items'])
            ->get()
            ->map(fn (PurchaseOrder $o) => [$o->po_number, $o->supplier?->company_name, $o->items->count(), number_format((float) $o->total_amount, 2), $o->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Purchase Order Report', [
            ['title' => 'Purchase Orders', 'columns' => ['PO #', 'Supplier', 'Line Items', 'Total', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportRequisitionReport()
    {
        $rows = PurchaseRequest::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with(['requester', 'department'])
            ->get()
            ->map(fn (PurchaseRequest $r) => [$r->pr_number, $r->requester?->full_name, $r->department?->name, number_format((float) ($r->expected_cost ?: $r->estimated_cost), 2), $r->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Requisition Report', [
            ['title' => 'Purchase Requisitions', 'columns' => ['Requisition #', 'Requester', 'Department', 'Expected Cost', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportSupplierReport()
    {
        $rows = Supplier::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->get()
            ->map(fn (Supplier $s) => [$s->supplier_number, $s->company_name, $s->category, $s->performance_rating, $s->risk_rating, $s->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Supplier Report', [
            ['title' => 'Suppliers', 'columns' => ['Supplier #', 'Name', 'Category', 'Rating', 'Risk', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportSupplierPerformanceReport()
    {
        $svc = app(ProcurementAnalyticsService::class);

        return app(ReportExportService::class)->toPdf('Supplier Performance Report', [], [
            ['label' => 'Average Supplier Performance', 'value' => number_format($svc->averageSupplierPerformance($this->companyId()), 1) . ' / 5'],
            ['label' => 'Invoice Matching Rate', 'value' => $svc->invoiceMatchingRatePercent($this->companyId()) . '%'],
        ], $this->companyId());
    }

    public function exportContractRegister()
    {
        $rows = SupplierContract::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('supplier')
            ->get()
            ->map(fn (SupplierContract $c) => [$c->contract_number, $c->supplier?->company_name, $c->contract_type, number_format((float) $c->contract_value, 2), $c->expiry_date?->format('d M Y'), $c->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Contract Register', [
            ['title' => 'Supplier Contracts & Framework Agreements', 'columns' => ['Contract #', 'Supplier', 'Type', 'Value', 'Expiry', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportGoodsReceivingReport()
    {
        $rows = GoodsReceivedNote::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('materialDelivery.material')
            ->get()
            ->map(fn (GoodsReceivedNote $g) => [$g->grn_number, $g->materialDelivery?->material?->name, $g->accepted_quantity, $g->rejected_quantity, $g->damaged_quantity, $g->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Goods Receiving Report', [
            ['title' => 'Goods Received Notes', 'columns' => ['GRN #', 'Material', 'Accepted', 'Rejected', 'Damaged', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportReturnReport()
    {
        $rows = PurchaseReturn::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('supplier')
            ->get()
            ->map(fn (PurchaseReturn $r) => [$r->return_number, $r->supplier?->company_name, $r->reason, $r->replacement_status, $r->status])
            ->all();

        return app(ReportExportService::class)->toPdf('Return Report', [
            ['title' => 'Purchase Returns', 'columns' => ['Return #', 'Supplier', 'Reason', 'Replacement Status', 'Status'], 'rows' => $rows],
        ], [], $this->companyId());
    }

    public function exportSpendAnalysisReport()
    {
        $svc = app(ProcurementAnalyticsService::class);
        $companyId = $this->companyId();

        $deptRows = collect($svc->spendByDepartment($companyId))->map(fn ($amount, $label) => [$label, number_format($amount, 2)])->values()->all();
        $supplierRows = collect($svc->spendBySupplier($companyId))->map(fn ($amount, $label) => [$label, number_format($amount, 2)])->values()->all();

        return app(ReportExportService::class)->toPdf('Spend Analysis Report', [
            ['title' => 'Spend by Department (90 Days)', 'columns' => ['Department', 'Spend'], 'rows' => $deptRows],
            ['title' => 'Spend by Supplier (90 Days)', 'columns' => ['Supplier', 'Spend'], 'rows' => $supplierRows],
        ], [], $companyId);
    }

    public function exportBudgetUtilizationReport()
    {
        $companyId = $this->companyId();
        $budgetSvc = app(BudgetValidationService::class);

        $rows = ProcurementBudget::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('financial_year', (string) now()->year)
            ->get()
            ->map(function (ProcurementBudget $budget) use ($companyId, $budgetSvc) {
                $result = $budgetSvc->validate($companyId, [
                    'business_unit_id' => $budget->business_unit_id,
                    'department_id' => $budget->department_id,
                    'cost_centre_id' => $budget->cost_centre_id,
                    'project_id' => $budget->project_id,
                ], 0, $budget->financial_year);

                return [$budget->budget_number, $budget->category, number_format((float) $budget->approved_budget, 2), number_format($result['committed'], 2), number_format($result['remaining'], 2)];
            })
            ->all();

        return app(ReportExportService::class)->toPdf('Budget Utilization Report (' . now()->year . ')', [
            ['title' => 'Budgets', 'columns' => ['Budget #', 'Category', 'Approved', 'Committed', 'Remaining'], 'rows' => $rows],
        ], [], $companyId);
    }

    public function exportExecutiveReport()
    {
        $svc = app(ProcurementAnalyticsService::class);
        $companyId = $this->companyId();

        $kpis = [
            ['label' => 'Purchase Requests', 'value' => (string) $svc->purchaseRequestsCount($companyId)],
            ['label' => 'Purchase Orders', 'value' => (string) $svc->purchaseOrdersCount($companyId)],
            ['label' => 'Spend This Month', 'value' => number_format($svc->spendThisMonthAmount($companyId), 2)],
            ['label' => 'Budget Utilization', 'value' => $svc->budgetUtilizationPercent($companyId) . '%'],
            ['label' => 'Purchase Savings', 'value' => number_format($svc->purchaseSavingsAmount($companyId), 2)],
            ['label' => 'Avg Procurement Cycle', 'value' => number_format($svc->averageProcurementCycleDays($companyId), 1) . ' days'],
            ['label' => 'Invoice Matching Rate', 'value' => $svc->invoiceMatchingRatePercent($companyId) . '%'],
            ['label' => 'Open RFQs', 'value' => (string) $svc->openRfqsCount($companyId)],
            ['label' => 'Open Contracts', 'value' => (string) $svc->openContractsCount($companyId)],
            ['label' => 'Contract Compliance', 'value' => $svc->contractCompliancePercent($companyId) . '%'],
        ];

        return app(ReportExportService::class)->toPdf('Executive Procurement Report', [], $kpis, $companyId);
    }
}
