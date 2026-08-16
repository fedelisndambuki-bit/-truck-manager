<?php

namespace App\Services\Ai\DataProviders;

use App\Contracts\AiDataProviderInterface;
use App\Services\Ai\AiComparisonEngine;
use App\Services\Ai\AiTrendAnalyzer;
use App\Services\ProcurementAnalyticsService;

/**
 * Procurement — all-industry shared module (Supplier/PurchaseOrder/
 * PurchaseRequest/GoodsReceivedNote have no industry gate). Wraps the
 * existing ProcurementAnalyticsService rather than re-querying models.
 */
class ProcurementAiDataProvider implements AiDataProviderInterface
{
    public function __construct(
        protected ProcurementAnalyticsService $procurement,
        protected AiComparisonEngine $comparisons,
        protected AiTrendAnalyzer $trends,
    ) {
    }

    public function key(): string
    {
        return 'procurement';
    }

    public function label(): string
    {
        return 'Procurement';
    }

    public function requiredPermission(): ?string
    {
        return null;
    }

    public function appliesToIndustry(?string $industry): bool
    {
        return true;
    }

    public function summary(int $companyId, ?array $period = null): array
    {
        // NOTE: monthlyProcurementTrend() sums PurchaseOrder value (committed
        // spend, by order date) — a genuinely different figure from
        // spendThisMonthAmount() below (invoiced/paid SupplierInvoice
        // amount). Kept clearly separate rather than implying they're the
        // same "spend", since conflating them would misrepresent the data.
        $orderValueTrend = $this->procurement->monthlyProcurementTrend($companyId, 6);
        $months = array_values($orderValueTrend);
        $currentMonthOrderValue = $months[count($months) - 1] ?? 0.0;
        $previousMonthOrderValue = $months[count($months) - 2] ?? 0.0;

        return [
            'purchase_requests' => $this->procurement->purchaseRequestsCount($companyId),
            'pending_approvals' => $this->procurement->pendingApprovalsCount($companyId),
            'purchase_orders' => $this->procurement->purchaseOrdersCount($companyId),
            'goods_in_transit' => $this->procurement->goodsInTransitCount($companyId),
            'invoiced_spend_this_month' => $this->procurement->spendThisMonthAmount($companyId),
            'purchase_order_value_month_over_month_comparison' => $this->comparisons->compare('purchase_order_value', $currentMonthOrderValue, $previousMonthOrderValue, 'KES', 'this month', 'last month'),
            'purchase_order_value_trend_6mo' => $this->trends->analyze($orderValueTrend),
            'open_rfqs' => $this->procurement->openRfqsCount($companyId),
            'open_contracts' => $this->procurement->openContractsCount($companyId),
            'average_supplier_performance_percent' => $this->procurement->averageSupplierPerformance($companyId),
        ];
    }

    public function tools(): array
    {
        return [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_procurement_summary',
                    'description' => 'Get purchase request/order counts, pending approvals, invoiced spend this month, purchase order value (with a month-over-month comparison and 6-month trend), open RFQs/contracts, and supplier performance.',
                    'parameters' => ['type' => 'object', 'properties' => new \stdClass(), 'required' => []],
                ],
            ],
        ];
    }

    public function callTool(string $name, array $arguments, int $companyId): array
    {
        return $this->summary($companyId);
    }
}
