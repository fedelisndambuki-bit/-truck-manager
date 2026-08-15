<?php

namespace App\Filament\Clusters\StockpileManagement\Widgets;

use App\Services\Quarry\StockpileAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class InventoryTurnoverChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Inventory Turnover (8 Weeks)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(StockpileAnalyticsService::class)->inventoryTurnoverTrend($companyId);
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
                'label' => 'Turnover Ratio', 'data' => $values, 'fill' => 'start', 'tension' => 0.4,
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
