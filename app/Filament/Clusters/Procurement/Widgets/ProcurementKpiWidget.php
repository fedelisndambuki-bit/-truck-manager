<?php

namespace App\Filament\Clusters\Procurement\Widgets;

use App\Services\ProcurementAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ProcurementKpiWidget extends BaseWidget
{
    protected static ?int $sort = -2;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(ProcurementAnalyticsService::class);

        $pending = $svc->pendingApprovalsCount($companyId);
        $goodsInTransit = $svc->goodsInTransitCount($companyId);
        $budgetUtilization = $svc->budgetUtilizationPercent($companyId);
        $contractCompliance = $svc->contractCompliancePercent($companyId);
        $matchRate = $svc->invoiceMatchingRatePercent($companyId);

        return [
            Stat::make('Purchase Requests', (string) $svc->purchaseRequestsCount($companyId))->descriptionIcon('heroicon-m-document-text')->color('info'),
            Stat::make('Pending Approvals', (string) $pending)->descriptionIcon('heroicon-m-clock')->color($pending > 0 ? 'warning' : 'success'),
            Stat::make('Approved Requisitions', (string) $svc->approvedRequisitionsCount($companyId))->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Purchase Orders', (string) $svc->purchaseOrdersCount($companyId))->descriptionIcon('heroicon-m-document-check')->color('info'),
            Stat::make('Goods In Transit', (string) $goodsInTransit)->descriptionIcon('heroicon-m-truck')->color($goodsInTransit > 0 ? 'warning' : 'success'),
            Stat::make('Goods Received (Month)', (string) $svc->goodsReceivedThisMonthCount($companyId))->descriptionIcon('heroicon-m-clipboard-document-check')->color('success'),
            Stat::make('Supplier Deliveries (Month)', (string) $svc->supplierDeliveriesThisMonthCount($companyId))->descriptionIcon('heroicon-m-building-storefront')->color('info'),
            Stat::make('Supplier Performance', number_format($svc->averageSupplierPerformance($companyId), 1) . ' / 5')->descriptionIcon('heroicon-m-star')->color('success'),
            Stat::make('Contract Compliance', $contractCompliance . '%')->descriptionIcon('heroicon-m-shield-check')->color($contractCompliance >= 90 ? 'success' : 'warning'),
            Stat::make('Spend Today', number_format($svc->spendTodayAmount($companyId), 0) . ' ' . session('currency', 'KES'))->descriptionIcon('heroicon-m-banknotes')->color('gray'),
            Stat::make('Spend This Month', number_format($svc->spendThisMonthAmount($companyId), 0) . ' ' . session('currency', 'KES'))->descriptionIcon('heroicon-m-currency-dollar')->color('gray'),
            Stat::make('Budget Utilization', $budgetUtilization . '%')->descriptionIcon('heroicon-m-chart-pie')->color($budgetUtilization <= 90 ? 'success' : 'danger'),
            Stat::make('Purchase Savings', number_format($svc->purchaseSavingsAmount($companyId), 0) . ' ' . session('currency', 'KES'))->descriptionIcon('heroicon-m-arrow-trending-down')->color('success'),
            Stat::make('Avg Procurement Cycle', number_format($svc->averageProcurementCycleDays($companyId), 1) . ' days')->descriptionIcon('heroicon-m-calendar-days')->color('info'),
            Stat::make('Invoice Matching Rate', $matchRate . '%')->descriptionIcon('heroicon-m-check-badge')->color($matchRate >= 90 ? 'success' : 'warning'),
            Stat::make('Open RFQs', (string) $svc->openRfqsCount($companyId))->descriptionIcon('heroicon-m-envelope-open')->color('info'),
            Stat::make('Open Contracts', (string) $svc->openContractsCount($companyId))->descriptionIcon('heroicon-m-document-text')->color('info'),
        ];
    }
}
