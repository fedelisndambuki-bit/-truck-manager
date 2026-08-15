<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Widgets;

use App\Services\Quarry\CrushingPlantAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PlantYieldAnalysisChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Yield Analysis (Production Runs)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(CrushingPlantAnalyticsService::class)->yieldAnalysisTrend($companyId);
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
                'label' => 'Yield %', 'data' => $values, 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#14b8a6', 'backgroundColor' => 'rgba(20, 184, 166, 0.1)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
