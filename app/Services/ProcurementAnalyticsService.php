<?php

namespace App\Services;

use App\Models\GoodsReceivedNote;
use App\Models\PurchaseOrder;
use App\Models\PurchaseRequest;
use App\Models\ProcurementBudget;
use App\Models\RequestForQuotation;
use App\Models\Supplier;
use App\Models\SupplierContract;
use App\Models\SupplierInvoice;

/**
 * Orchestration layer for the Procurement Dashboard/Reports — mirrors
 * CrushingPlantAnalyticsService/EamAnalyticsService's shape (explicit
 * companyId param + query-per-metric). Built up phase by phase alongside
 * the tables each metric depends on.
 */
class ProcurementAnalyticsService
{
    protected function requests(?int $companyId)
    {
        return PurchaseRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    protected function orders(?int $companyId)
    {
        return PurchaseOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
    }

    // ── Dashboard KPIs ───────────────────────────────────────────────────────

    public function purchaseRequestsCount(?int $companyId): int
    {
        return $this->requests($companyId)->count();
    }

    public function pendingApprovalsCount(?int $companyId): int
    {
        return $this->requests($companyId)->where('status', 'Pending')->count();
    }

    public function approvedRequisitionsCount(?int $companyId): int
    {
        return $this->requests($companyId)->where('status', 'Approved')->count();
    }

    public function purchaseOrdersCount(?int $companyId): int
    {
        return $this->orders($companyId)->count();
    }

    public function goodsInTransitCount(?int $companyId): int
    {
        return $this->orders($companyId)->where('status', 'Approved')->whereNull('delivery_confirmed_at')->count();
    }

    public function goodsReceivedThisMonthCount(?int $companyId): int
    {
        return GoodsReceivedNote::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->whereMonth('updated_at', now()->month)
            ->whereYear('updated_at', now()->year)
            ->count();
    }

    public function supplierDeliveriesThisMonthCount(?int $companyId): int
    {
        return $this->orders($companyId)
            ->whereNotNull('delivery_confirmed_at')
            ->whereMonth('delivery_confirmed_at', now()->month)
            ->whereYear('delivery_confirmed_at', now()->year)
            ->distinct('supplier_id')
            ->count('supplier_id');
    }

    public function averageSupplierPerformance(?int $companyId): float
    {
        return round((float) Supplier::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('performance_rating')
            ->avg('performance_rating'), 1);
    }

    public function contractCompliancePercent(?int $companyId): float
    {
        $total = SupplierContract::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();

        if ($total <= 0) {
            return 100.0;
        }

        $compliant = SupplierContract::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where(function ($q) {
                $q->where('status', 'Active')->where(function ($qq) {
                    $qq->whereNull('expiry_date')->orWhere('expiry_date', '>=', now());
                });
            })
            ->count();

        return round(($compliant / $total) * 100, 1);
    }

    public function spendTodayAmount(?int $companyId): float
    {
        return (float) SupplierInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Approved', 'Paid'])
            ->whereDate('invoice_date', today())
            ->sum('total_amount');
    }

    public function spendThisMonthAmount(?int $companyId): float
    {
        return (float) SupplierInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Approved', 'Paid'])
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->sum('total_amount');
    }

    public function budgetUtilizationPercent(?int $companyId): float
    {
        $budgetSvc = app(BudgetValidationService::class);

        $budgets = ProcurementBudget::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->where('financial_year', (string) now()->year)
            ->get();

        if ($budgets->isEmpty()) {
            return 0.0;
        }

        $utilizations = $budgets->map(function (ProcurementBudget $budget) use ($companyId, $budgetSvc) {
            $result = $budgetSvc->validate($companyId, [
                'business_unit_id' => $budget->business_unit_id,
                'department_id' => $budget->department_id,
                'cost_centre_id' => $budget->cost_centre_id,
                'project_id' => $budget->project_id,
            ], 0, $budget->financial_year);

            return (float) $budget->approved_budget > 0
                ? ($result['committed'] / (float) $budget->approved_budget) * 100
                : 0.0;
        });

        return round($utilizations->avg(), 1);
    }

    /**
     * Sum, across every Awarded RFQ, of (highest quotation - awarded
     * quotation) — the amount saved by not simply taking the first/priciest
     * offer.
     */
    public function purchaseSavingsAmount(?int $companyId): float
    {
        $savings = 0.0;

        RequestForQuotation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Awarded')
            ->with('quotations')
            ->get()
            ->each(function (RequestForQuotation $rfq) use (&$savings) {
                $accepted = $rfq->quotations->firstWhere('status', 'Accepted');
                $highest = $rfq->quotations->max('total_amount');

                if ($accepted && $highest) {
                    $savings += max(0, (float) $highest - (float) $accepted->total_amount);
                }
            });

        return round($savings, 2);
    }

    public function averageProcurementCycleDays(?int $companyId): float
    {
        $orders = $this->orders($companyId)
            ->whereNotNull('purchase_request_id')
            ->with('purchaseRequest')
            ->get()
            ->filter(fn (PurchaseOrder $o) => $o->purchaseRequest !== null);

        if ($orders->isEmpty()) {
            return 0.0;
        }

        return round($orders->avg(fn (PurchaseOrder $o) => $o->purchaseRequest->created_at->diffInDays($o->created_at)), 1);
    }

    public function invoiceMatchingRatePercent(?int $companyId): float
    {
        $checked = SupplierInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('matching_status', ['Matched', 'Discrepancy'])
            ->count();

        if ($checked <= 0) {
            return 0.0;
        }

        $matched = SupplierInvoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('matching_status', 'Matched')
            ->count();

        return round(($matched / $checked) * 100, 1);
    }

    public function openRfqsCount(?int $companyId): int
    {
        return RequestForQuotation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Draft', 'Open'])
            ->count();
    }

    public function openContractsCount(?int $companyId): int
    {
        return SupplierContract::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->count();
    }

    // ── Charts ───────────────────────────────────────────────────────────────

    public function spendByDepartment(?int $companyId, int $days = 90): array
    {
        return $this->orders($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->whereNotNull('department_id')
            ->with('department')
            ->get()
            ->groupBy(fn (PurchaseOrder $o) => $o->department?->name ?? 'Unassigned')
            ->map(fn ($group) => round((float) $group->sum('total_amount'), 2))
            ->sortDesc()
            ->take(8)
            ->toArray();
    }

    public function spendBySupplier(?int $companyId, int $days = 90): array
    {
        return $this->orders($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('supplier')
            ->get()
            ->groupBy(fn (PurchaseOrder $o) => $o->supplier?->company_name ?? 'Unknown')
            ->map(fn ($group) => round((float) $group->sum('total_amount'), 2))
            ->sortDesc()
            ->take(8)
            ->toArray();
    }

    public function spendByCategory(?int $companyId, int $days = 90): array
    {
        return $this->orders($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->with('supplier')
            ->get()
            ->groupBy(fn (PurchaseOrder $o) => $o->supplier?->category ?? 'Uncategorized')
            ->map(fn ($group) => round((float) $group->sum('total_amount'), 2))
            ->sortDesc()
            ->toArray();
    }

    public function monthlyProcurementTrend(?int $companyId, int $months = 6): array
    {
        $trend = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $label = $month->format('M Y');

            $trend[$label] = round((float) $this->orders($companyId)
                ->whereMonth('created_at', $month->month)
                ->whereYear('created_at', $month->year)
                ->sum('total_amount'), 2);
        }

        return $trend;
    }

    public function purchaseTrend(?int $companyId, int $days = 14): array
    {
        return $this->requests($companyId)
            ->where('created_at', '>=', now()->subDays($days))
            ->get()
            ->groupBy(fn (PurchaseRequest $r) => $r->created_at->format('Y-m-d'))
            ->map(fn ($group) => $group->count())
            ->sortKeys()
            ->toArray();
    }

    public function contractExpiryTrend(?int $companyId, int $months = 6): array
    {
        $trend = [];

        for ($i = 0; $i < $months; $i++) {
            $month = now()->addMonths($i);
            $label = $month->format('M Y');

            $trend[$label] = SupplierContract::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('expiry_date')
                ->whereMonth('expiry_date', $month->month)
                ->whereYear('expiry_date', $month->year)
                ->count();
        }

        return $trend;
    }

    public function budgetConsumption(?int $companyId): array
    {
        $budgetSvc = app(BudgetValidationService::class);

        return ProcurementBudget::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->where('financial_year', (string) now()->year)
            ->get()
            ->mapWithKeys(function (ProcurementBudget $budget) use ($companyId, $budgetSvc) {
                $result = $budgetSvc->validate($companyId, [
                    'business_unit_id' => $budget->business_unit_id,
                    'department_id' => $budget->department_id,
                    'cost_centre_id' => $budget->cost_centre_id,
                    'project_id' => $budget->project_id,
                ], 0, $budget->financial_year);

                $label = $budget->category ?? $budget->budget_number;

                return [$label => (float) $budget->approved_budget > 0 ? round(($result['committed'] / (float) $budget->approved_budget) * 100, 1) : 0.0];
            })
            ->toArray();
    }
}
