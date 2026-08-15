<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\ChartOfAccount;
use App\Models\FinancialPeriod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinanceKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');
        $fmt = fn (float $v) => $currency . ' ' . number_format($v, 0);

        if (! $companyId) {
            return [Stat::make('Total Assets', $fmt(0))];
        }

        $accounts = ChartOfAccount::withoutGlobalScope('company')
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))
            ->get();

        $totalsByType = ['Asset' => 0.0, 'Liability' => 0.0, 'Equity' => 0.0, 'Revenue' => 0.0, 'Expense' => 0.0, 'CostOfSales' => 0.0];

        foreach ($accounts as $account) {
            $totalsByType[$account->type] = ($totalsByType[$account->type] ?? 0) + $account->balance($companyId);
        }

        $netIncome = $totalsByType['Revenue'] - $totalsByType['Expense'] - $totalsByType['CostOfSales'];

        $openPeriods = FinancialPeriod::where('company_id', $companyId)->where('status', 'Open')->count();
        $closedPeriods = FinancialPeriod::where('company_id', $companyId)->where('status', '!=', 'Open')->count();

        return [
            Stat::make('Total Assets', $fmt($totalsByType['Asset']))->descriptionIcon('heroicon-m-building-library')->color('info'),
            Stat::make('Total Liabilities', $fmt($totalsByType['Liability']))->descriptionIcon('heroicon-m-credit-card')->color('warning'),
            Stat::make('Total Revenue', $fmt($totalsByType['Revenue']))->descriptionIcon('heroicon-m-arrow-trending-up')->color('success'),
            Stat::make('Total Expenses', $fmt($totalsByType['Expense'] + $totalsByType['CostOfSales']))->descriptionIcon('heroicon-m-arrow-trending-down')->color('danger'),
            Stat::make('Net Income', $fmt($netIncome))->descriptionIcon('heroicon-m-currency-dollar')->color($netIncome >= 0 ? 'success' : 'danger'),
            Stat::make('Open / Closed Periods', "{$openPeriods} / {$closedPeriods}")->descriptionIcon('heroicon-m-calendar-days')->color('gray'),
        ];
    }
}
