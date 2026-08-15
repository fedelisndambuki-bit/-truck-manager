<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class FinancialSummaryWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $user = Auth::user();
        if (!$user) return [];

        try {
            $revenue = Wallet::where('company_id', $user->company_id)->where('type', 'revenue')->first();
            $expense = Wallet::where('company_id', $user->company_id)->where('type', 'expense')->first();
            
            return [
                Stat::make('Revenue Wallet', ($revenue?->available_balance / 100 ?? 0) . ' KES')
                    ->description('Available balance')
                    ->icon('heroicon-o-currency-dollar'),
                Stat::make('Expense Wallet', ($expense?->available_balance / 100 ?? 0) . ' KES')
                    ->description('Operational funds')
                    ->icon('heroicon-o-credit-card'),
            ];
        } catch (\Exception $e) {
            return [
                Stat::make('Financials', 'Database connection pending')
                    ->description('Configure PostgreSQL to see balances')
                    ->icon('heroicon-o-exclamation-triangle'),
            ];
        }
    }
}
