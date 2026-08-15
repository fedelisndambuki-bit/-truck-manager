<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Services\Analytics\RevenueMetricsService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProfitLossOverview extends BaseWidget
{
    // Important: Setting a polling interval helps keep the data fresh
    protected static ?string $pollingInterval = '15s';

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id;
        $revenue = app(RevenueMetricsService::class)->cashRevenue($companyId);
        $expenses = floatval(
            ($companyId ? Expense::where('company_id', $companyId) : Expense::query())->sum('amount') ?? 0
        );
        $netProfit = $revenue - $expenses;

        // 2. Return the Stat array using the correct Filament classes
        return [
            Stat::make('Total Revenue', 'KES '.number_format($revenue, 2))
                ->description('Total income received')
                ->descriptionIcon('heroicon-s-arrow-trending-up')
                ->color('success'),

            Stat::make('Total Expenses', 'KES '.number_format($expenses, 2))
                ->description('Total money spent')
                ->descriptionIcon('heroicon-s-arrow-trending-down')
                ->color('danger'),

            Stat::make('Net Profit', 'KES '.number_format($netProfit, 2))
                ->description($netProfit < 0 ? 'Currently in loss' : 'Profitable')
                ->descriptionIcon($netProfit < 0 ? 'heroicon-s-arrow-trending-down' : 'heroicon-s-arrow-trending-up')
                ->color($netProfit < 0 ? 'danger' : 'success'),
        ];
    }
}
