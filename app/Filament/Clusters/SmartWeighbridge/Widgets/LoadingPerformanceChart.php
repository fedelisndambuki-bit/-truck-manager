<?php

namespace App\Filament\Clusters\SmartWeighbridge\Widgets;

use App\Services\Quarry\WeighbridgeAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class LoadingPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Loading Performance (Recent)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(WeighbridgeAnalyticsService::class)->loadingPerformanceTrend($companyId);
            $labels = array_keys($raw);
            $values = array_map('floatval', array_values($raw));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'borderColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Loading Duration (min)', 'data' => $values, 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#f59e0b', 'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
