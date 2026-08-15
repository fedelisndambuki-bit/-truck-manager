<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use App\Models\WalletFunding;
use App\Models\Payout;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class WalletOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $user = auth()->user();
        $companyId = $user->company_id;

        if (!$companyId) {
            // Fallback for Super Admin or users without a company
            return [];
        }

        $revenueWallet = Wallet::where('company_id', $companyId)->where('type', 'revenue')->first();

        $balance = $revenueWallet ? ($revenueWallet->available_balance / 100) : 0;

        $totalFunded = WalletFunding::where('company_id', $companyId)
            ->where('status', 'successful')
            ->sum('amount') / 100;

        $pendingPayouts = Payout::where('company_id', $companyId)
            ->where('status', 'pending')
            ->sum('amount') / 100;

        return [
            Stat::make('Wallet Balance', 'KES ' . number_format($balance, 2))
                ->description('Available for payouts')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
            Stat::make('Total Funded', 'KES ' . number_format($totalFunded, 2))
                ->description('Lifetime funding')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('info'),
            Stat::make('Pending Payouts', 'KES ' . number_format($pendingPayouts, 2))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
        ];
    }
}
