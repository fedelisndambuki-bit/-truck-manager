<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CostBreakdownChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Cost Breakdown';

    protected function getData(): array
    {
        try {
            $breakdown = app(ProjectFinanceService::class)->companyCostBreakdown(Auth::user()?->company_id);
        } catch (\Exception $e) {
            $breakdown = [];
        }

        if (empty(array_filter($breakdown))) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['Waiting for Data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => array_values($breakdown),
                    'backgroundColor' => ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b', '#ec4899', '#0891b2'],
                ],
            ],
            'labels' => array_keys($breakdown),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
