<?php

namespace App\Filament\Clusters\SmartWeighbridge\Widgets;

use App\Services\Quarry\WeighbridgeAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class QueueAnalysisChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Queue Analysis';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(WeighbridgeAnalyticsService::class)->queueAnalysis($companyId);
            $labels = array_keys($raw);
            $values = array_map('intval', array_values($raw));
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
            'datasets' => [[
                'label' => 'Trucks',
                'data' => $values,
                'backgroundColor' => ['#3b82f6', '#f59e0b', '#8b5cf6', '#10b981', '#06b6d4', '#eab308', '#ef4444', '#84cc16'],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
