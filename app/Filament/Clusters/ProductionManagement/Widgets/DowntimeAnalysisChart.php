<?php

namespace App\Filament\Clusters\ProductionManagement\Widgets;

use App\Services\Quarry\QuarryAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DowntimeAnalysisChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Downtime by Reason (30 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $byReason = app(QuarryAnalyticsService::class)->downtimeByReason($companyId);
            $labels = array_keys($byReason);
            $values = array_values($byReason);
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Downtime (min)',
                    'data' => $values,
                    'backgroundColor' => ['#ef4444', '#f97316', '#f59e0b', '#eab308', '#84cc16', '#06b6d4', '#6366f1', '#8b5cf6'],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
