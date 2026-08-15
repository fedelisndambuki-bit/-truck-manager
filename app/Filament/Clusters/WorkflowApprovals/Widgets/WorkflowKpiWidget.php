<?php

namespace App\Filament\Clusters\WorkflowApprovals\Widgets;

use App\Services\WorkflowAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WorkflowKpiWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(WorkflowAnalyticsService::class);

        return [
            Stat::make('Pending Requests', (string) $svc->pendingRequestsCount($companyId))->descriptionIcon('heroicon-m-inbox-arrow-down')->color('info'),
            Stat::make('Approved Today', (string) $svc->approvedTodayCount($companyId))->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Rejected Today', (string) $svc->rejectedTodayCount($companyId))->descriptionIcon('heroicon-m-x-circle')->color('danger'),
            Stat::make('Average Approval Time', $svc->averageApprovalTimeHours($companyId) . ' hrs')->descriptionIcon('heroicon-m-clock')->color('info'),
            Stat::make('Approval SLA', $svc->approvalSlaCompliancePercent($companyId) . '%')->descriptionIcon('heroicon-m-shield-check')->color($svc->approvalSlaCompliancePercent($companyId) >= 90 ? 'success' : 'warning'),
            Stat::make('Escalated Requests', (string) $svc->escalatedRequestsCount($companyId))->descriptionIcon('heroicon-m-arrow-trending-up')->color($svc->escalatedRequestsCount($companyId) > 0 ? 'warning' : 'success'),
            Stat::make('Delegated Requests', (string) $svc->delegatedRequestsCount($companyId))->descriptionIcon('heroicon-m-user-plus')->color('gray'),
            Stat::make('Active Workflows', (string) $svc->activeWorkflowsCount($companyId))->descriptionIcon('heroicon-m-squares-plus')->color('success'),
        ];
    }
}
