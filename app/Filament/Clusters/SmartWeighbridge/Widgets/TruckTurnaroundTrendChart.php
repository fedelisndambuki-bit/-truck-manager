<?php

namespace App\Filament\Clusters\SmartWeighbridge\Widgets;

use App\Services\Quarry\WeighbridgeAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TruckTurnaroundTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Truck Turnaround Trend (14 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(WeighbridgeAnalyticsService::class)->truckTurnaroundTrend($companyId);
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
                'label' => 'Avg. Turnaround (min)', 'data' => $values, 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#8b5cf6', 'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
