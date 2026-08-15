<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\Project;
use App\Models\RetentionEntry;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinanceKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');
        $service = app(ProjectFinanceService::class);

        $projects = Project::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get();

        $totalContractValue = (float) $projects->sum(fn (Project $p) => (float) ($p->contract_value ?: $p->budget ?: 0));

        $approvedBudget = (float) \App\Models\ProjectBudget::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Approved')
            ->get()
            ->sum(fn ($b) => (float) ($b->revised_budget > 0 ? $b->revised_budget : $b->approved_budget));

        if ($approvedBudget <= 0) {
            $approvedBudget = $totalContractValue;
        }

        $totalCosts = 0.0;
        $totalRevenue = 0.0;
        $outstandingClient = 0.0;
        $outstandingSupplier = 0.0;

        foreach ($projects as $project) {
            $totalCosts += $service->totalActualCost($project);
            $totalRevenue += $service->totalRevenue($project);
            $outstandingClient += $service->outstandingClientAmount($project);
            $outstandingSupplier += $service->outstandingSupplierAmount($project);
        }

        $budgetUtilization = $approvedBudget > 0 ? round(($totalCosts / $approvedBudget) * 100) : 0;
        $remainingBudget = max(0, $approvedBudget - $totalCosts);
        $grossProfit = $totalRevenue - $totalCosts;
        $profitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 1) : 0;

        $retentionHeld = (float) RetentionEntry::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->sum(fn (RetentionEntry $r) => max(0, (float) $r->held_amount - (float) $r->released_amount));

        $cashFlow = $totalRevenue - $totalCosts;

        $fmt = fn (float $v) => $currency . ' ' . number_format($v, 0);

        return [
            Stat::make('Total Contract Value', $fmt($totalContractValue))->descriptionIcon('heroicon-m-document-text')->color('info'),
            Stat::make('Approved Budget', $fmt($approvedBudget))->descriptionIcon('heroicon-m-calculator')->color('info'),
            Stat::make('Budget Utilized', $budgetUtilization . '%')->descriptionIcon('heroicon-m-chart-pie')->color($budgetUtilization > 90 ? 'danger' : ($budgetUtilization > 75 ? 'warning' : 'success')),
            Stat::make('Remaining Budget', $fmt($remainingBudget))->descriptionIcon('heroicon-m-banknotes')->color('success'),
            Stat::make('Total Revenue', $fmt($totalRevenue))->descriptionIcon('heroicon-m-arrow-trending-up')->color('success'),
            Stat::make('Total Costs', $fmt($totalCosts))->descriptionIcon('heroicon-m-arrow-trending-down')->color('warning'),
            Stat::make('Gross Profit', $fmt($grossProfit))->descriptionIcon('heroicon-m-currency-dollar')->color($grossProfit >= 0 ? 'success' : 'danger'),
            Stat::make('Profit Margin', $profitMargin . '%')->descriptionIcon('heroicon-m-presentation-chart-line')->color($profitMargin >= 0 ? 'success' : 'danger'),
            Stat::make('Outstanding Client Payments', $fmt($outstandingClient))->descriptionIcon('heroicon-m-exclamation-circle')->color('warning'),
            Stat::make('Outstanding Supplier Payments', $fmt($outstandingSupplier))->descriptionIcon('heroicon-m-truck')->color('warning'),
            Stat::make('Retention Held', $fmt($retentionHeld))->descriptionIcon('heroicon-m-lock-closed')->color('gray'),
            Stat::make('Cash Flow Position', $fmt($cashFlow))->descriptionIcon('heroicon-m-scale')->color($cashFlow >= 0 ? 'success' : 'danger'),
        ];
    }
}
