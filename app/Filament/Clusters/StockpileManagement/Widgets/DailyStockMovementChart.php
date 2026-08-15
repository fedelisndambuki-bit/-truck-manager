<?php

namespace App\Filament\Clusters\StockpileManagement\Widgets;

use App\Services\Quarry\StockpileAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DailyStockMovementChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Daily Stock Movement (14 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(StockpileAnalyticsService::class)->dailyStockMovement($companyId);
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
                'label' => 'Tonnes Moved', 'data' => $values, 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#06b6d4', 'backgroundColor' => 'rgba(6, 182, 212, 0.1)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
