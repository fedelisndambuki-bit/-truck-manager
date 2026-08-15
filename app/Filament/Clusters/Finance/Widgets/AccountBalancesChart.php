<?php

namespace App\Filament\Clusters\Finance\Widgets;

use App\Models\ChartOfAccount;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AccountBalancesChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Balances by Account Type';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        if (! $companyId) {
            return ['datasets' => [['data' => [1], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['No Data']];
        }

        $accounts = ChartOfAccount::withoutGlobalScope('company')
            ->where(fn ($q) => $q->whereNull('company_id')->orWhere('company_id', $companyId))
            ->get();

        $totalsByType = [];

        foreach ($accounts as $account) {
            $totalsByType[$account->type] = ($totalsByType[$account->type] ?? 0) + abs($account->balance($companyId));
        }

        $totalsByType = array_filter($totalsByType);

        if (empty($totalsByType)) {
            return ['datasets' => [['data' => [1], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['Waiting for Data']];
        }

        return [
            'datasets' => [
                [
                    'data' => array_values($totalsByType),
                    'backgroundColor' => ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b'],
                ],
            ],
            'labels' => array_keys($totalsByType),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
