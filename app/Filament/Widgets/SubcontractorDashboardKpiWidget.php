<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\PaymentCertificate;
use App\Models\Project;
use App\Models\Subcontractor;
use App\Models\Variation;
use App\Models\WorkOrder;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class SubcontractorDashboardKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $subcontractors = Subcontractor::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $totalSubcontractors = (clone $subcontractors)->count();

        $contracts = Contract::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $activeContracts = (clone $contracts)->where('status', 'Active')->count();
        $expiringContracts = (clone $contracts)
            ->whereIn('status', ['Active', 'Suspended'])
            ->whereBetween('completion_date', [now(), now()->addDays(30)])
            ->count();

        $activeWorkOrders = WorkOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'In Progress')
            ->count();

        $pendingCertificates = PaymentCertificate::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Draft', 'Submitted'])
            ->count();

        $approvedPayments = PaymentCertificate::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Paid')
            ->sum('paid_amount');

        $openVariations = Variation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Requested', 'Under Review'])
            ->count();

        $complianceIssues = (clone $subcontractors)->get()->filter(fn ($s) => $s->compliance_status !== 'Compliant')->count();

        $avgPerformance = \Illuminate\Support\Facades\Schema::hasTable('subcontractor_performance_reviews')
            ? \App\Models\SubcontractorPerformanceReview::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->avg('overall_rating')
            : null;

        $activeProjectsWithSubcontractors = (clone $contracts)
            ->whereIn('status', ['Active', 'Suspended'])
            ->distinct('project_id')
            ->count('project_id');

        return [
            Stat::make('Total Subcontractors', (string) $totalSubcontractors)->color('info'),
            Stat::make('Active Contracts', (string) $activeContracts)->color('success'),
            Stat::make('Contracts Expiring Soon', (string) $expiringContracts)->color($expiringContracts > 0 ? 'warning' : 'success'),
            Stat::make('Active Work Orders', (string) $activeWorkOrders)->color('info'),
            Stat::make('Payment Certificates Pending', (string) $pendingCertificates)->color($pendingCertificates > 0 ? 'warning' : 'success'),
            Stat::make('Approved Payments', session('currency', 'KES') . ' ' . number_format($approvedPayments, 0))->color('success'),
            Stat::make('Open Variations', (string) $openVariations)->color($openVariations > 0 ? 'warning' : 'success'),
            Stat::make('Compliance Issues', (string) $complianceIssues)->color($complianceIssues > 0 ? 'danger' : 'success'),
            Stat::make('Avg. Performance Rating', $avgPerformance ? round($avgPerformance, 1) . ' / 5' : '—')->color('info'),
            Stat::make('Active Projects w/ Subcontractors', (string) $activeProjectsWithSubcontractors)->color('info'),
        ];
    }
}
