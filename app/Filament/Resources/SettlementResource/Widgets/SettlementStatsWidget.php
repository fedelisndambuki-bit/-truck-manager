<?php

namespace App\Filament\Resources\SettlementResource\Widgets;

use App\Models\Settlement;
use Filament\Support\Colors\Color;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SettlementStatsWidget extends BaseWidget
{
    protected int | string | array $columnSpan = 'full';

    protected function getStats(): array
    {
        // Dynamic stats calculation ported from SettlementController
        $totalEarned = Settlement::sum('net_amount');
        $thisMonth = Settlement::whereMonth('settlement_date', now()->month)->sum('net_amount');
        $pendingPayment = Settlement::where('status', 'PENDING')->sum('net_amount');
        $averageWeekly = Settlement::where('settlement_date', '>=', now()->subWeeks(5))->avg('net_amount');

        return [
            Stat::make(
                'TOTAL EARNED (YTD)',
                session('currency', 'KES') . ' ' . number_format($totalEarned, 2)
            )->description(
                Settlement::count().' settlements total'
            )->icon('heroicon-o-wallet')
                ->color(Color::Blue), // Using a color for consistency

            Stat::make(
                'THIS MONTH',
                session('currency', 'KES') . ' ' . number_format($thisMonth, 2)
            )->description(
                Settlement::whereMonth('settlement_date', now()->month)->count().' settlements processed'
            )->icon('heroicon-o-calendar')
                ->color(Color::Blue),

            Stat::make(
                'PENDING PAYMENT',
                session('currency', 'KES') . ' ' . number_format($pendingPayment, 2)
            )->description(
                'All pending payments'
            )->icon('heroicon-o-clock')
                ->color(Color::Blue),

            Stat::make(
                'AVERAGE WEEKLY',
                session('currency', 'KES') . ' ' . number_format($averageWeekly ?? 0, 2)
            )->description(
                'Based on last 5 weeks'
            )->icon('heroicon-o-chart-bar')
                ->color(Color::Blue),
        ];
    }
}
