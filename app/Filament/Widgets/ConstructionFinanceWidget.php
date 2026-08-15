<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Models\Project;
use App\Services\Analytics\RevenueMetricsService;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class ConstructionFinanceWidget extends BaseWidget
{
    protected static ?int $sort = 9;

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');

        $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();
        $totalBudget = $projects->sum('budget');

        // Full cost rollup (labour + materials + equipment + subcontractors +
        // procurement + site expenses + delay + quality rework), not just the
        // Expense table — matches BusinessIntelligenceService::totalCost().
        $financeService = app(ProjectFinanceService::class);
        $spent = $projects->sum(fn (Project $p) => $financeService->totalActualCost($p));

        $revenue = app(RevenueMetricsService::class)->cashRevenue($companyId);

        $outstanding = Invoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total_amount');

        $upcoming = Invoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('project_id')
            ->where('status', 'sent')
            ->where('due_date', '>=', now())
            ->sum('total_amount');

        $cashFlow = (float) $revenue - (float) $spent;
        $profitability = (float) $revenue - (float) $spent;

        return [
            Stat::make('Budget vs Actual', $currency . ' ' . number_format((float) $spent, 0) . ' / ' . number_format((float) $totalBudget, 0))->color('info'),
            Stat::make('Revenue vs Cost', $currency . ' ' . number_format((float) $revenue, 0) . ' / ' . number_format((float) $spent, 0))->color('success'),
            Stat::make('Outstanding Invoices', $currency . ' ' . number_format((float) $outstanding, 0))->color('warning'),
            Stat::make('Upcoming Payments', $currency . ' ' . number_format((float) $upcoming, 0))->color('gray'),
            Stat::make('Cash Flow', $currency . ' ' . number_format($cashFlow, 0))->color($cashFlow >= 0 ? 'success' : 'danger'),
            Stat::make('Project Profitability', $currency . ' ' . number_format($profitability, 0))->color($profitability >= 0 ? 'success' : 'danger'),
        ];
    }
}
