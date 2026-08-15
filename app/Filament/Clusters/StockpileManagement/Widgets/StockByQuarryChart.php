<?php

namespace App\Filament\Clusters\StockpileManagement\Widgets;

use App\Services\Quarry\StockpileAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class StockByQuarryChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Stock by Quarry';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(StockpileAnalyticsService::class)->stockByQuarry($companyId);
            $labels = array_keys($raw);
            $values = array_values($raw);
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Tonnes', 'data' => $values, 'backgroundColor' => '#3b82f6']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
