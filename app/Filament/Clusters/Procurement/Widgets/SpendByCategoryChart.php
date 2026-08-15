<?php

namespace App\Filament\Clusters\Procurement\Widgets;

use App\Services\ProcurementAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SpendByCategoryChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Spend by Category (90 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(ProcurementAnalyticsService::class)->spendByCategory($companyId);
        } catch (\Exception $e) {
            $raw = [];
        }

        if (empty($raw)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Spend', 'data' => array_values($raw),
                'backgroundColor' => ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899', '#14b8a6'],
            ]],
            'labels' => array_keys($raw),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
