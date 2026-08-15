<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\Expense;
use App\Models\Invoice;
use App\Services\Quarry\QuarryAnalyticsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class QuarryFinanceWidget extends BaseWidget
{
    protected static ?int $sort = -1;

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');
        $qa = app(QuarryAnalyticsService::class);

        $spent = Expense::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->sum('amount');

        $outstanding = Invoice::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['sent', 'overdue'])
            ->sum('total_amount');

        $revenue = $qa->revenueMonth($companyId);
        $cashFlow = $revenue - (float) $spent;

        return [
            Stat::make('Revenue This Month', $currency . ' ' . number_format($revenue, 0))->descriptionIcon('heroicon-m-arrow-trending-up')->color('success'),
            Stat::make('Costs This Month', $currency . ' ' . number_format((float) $spent, 0))->descriptionIcon('heroicon-m-arrow-trending-down')->color('warning'),
            Stat::make('Outstanding Invoices', $currency . ' ' . number_format((float) $outstanding, 0))->descriptionIcon('heroicon-m-exclamation-circle')->color('warning'),
            Stat::make('Cash Flow', $currency . ' ' . number_format($cashFlow, 0))->descriptionIcon('heroicon-m-banknotes')->color($cashFlow >= 0 ? 'success' : 'danger'),
        ];
    }
}
