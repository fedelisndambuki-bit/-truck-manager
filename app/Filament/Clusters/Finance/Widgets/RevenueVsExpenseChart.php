<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\LedgerEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class RevenueVsExpenseChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Revenue vs Expense (Last 6 Months)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $months = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));

        $revenue = [];
        $expense = [];
        $labels = [];

        foreach ($months as $month) {
            $labels[] = $month->format('M Y');

            $entries = $companyId
                ? LedgerEntry::withoutGlobalScope('company')
                    ->where('company_id', $companyId)
                    ->whereBetween('created_at', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                    ->get()
                : collect();

            $revenue[] = round($entries->whereBetween('account_code', [3000, 3999])->sum('credit') / 100, 2);
            $expense[] = round($entries->whereBetween('account_code', [4000, 4999])->sum('debit') / 100, 2);
        }

        return [
            'datasets' => [
                ['label' => 'Revenue', 'data' => $revenue, 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16,185,129,0.15)'],
                ['label' => 'Expense', 'data' => $expense, 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239,68,68,0.15)'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
