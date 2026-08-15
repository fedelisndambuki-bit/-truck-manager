<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use Filament\Pages\Page;

class RevenueAnalytics extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Revenue Analytics';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.super-admin.pages.revenue-analytics';

    public function getViewData(): array
    {
        $mrr = (float) Subscription::query()
            ->where('status', 'active')
            ->where('billing_cycle', 'monthly')
            ->sum('price');

        $yearlyToMonthly = (float) Subscription::query()
            ->where('status', 'active')
            ->where('billing_cycle', 'yearly')
            ->sum('price') / 12;

        $mrrTotal = $mrr + $yearlyToMonthly;
        $arr = $mrrTotal * 12;

        $startOfMonth = now()->startOfMonth();
        $endOfMonth = now()->endOfMonth();
        $newSignups = Company::query()->whereBetween('created_at', [$startOfMonth, $endOfMonth])->count();

        $churned = Subscription::query()->where('status', 'canceled')->whereBetween('updated_at', [$startOfMonth, $endOfMonth])->count();
        $activeBase = max(1, Subscription::query()->where('status', 'active')->count());
        $churnRate = round(($churned / $activeBase) * 100, 2);

        $planDistribution = Subscription::query()
            ->selectRaw('plan_name, count(*) as total')
            ->groupBy('plan_name')
            ->orderByDesc('total')
            ->get();

        $monthlySeries = collect(range(5, 0, -1))
            ->map(function (int $offset) {
                $month = now()->subMonths($offset);
                $revenue = (float) SubscriptionPayment::query()
                    ->where('status', 'paid')
                    ->whereYear('paid_at', $month->year)
                    ->whereMonth('paid_at', $month->month)
                    ->sum('amount');
                $signups = Company::query()
                    ->whereYear('created_at', $month->year)
                    ->whereMonth('created_at', $month->month)
                    ->count();

                return [
                    'label' => $month->format('M Y'),
                    'revenue' => $revenue,
                    'signups' => $signups,
                ];
            })
            ->values();

        return [
            'mrr' => $mrrTotal,
            'arr' => $arr,
            'churnRate' => $churnRate,
            'newSignups' => $newSignups,
            'planDistribution' => $planDistribution,
            'monthlySeries' => $monthlySeries,
        ];
    }
}
