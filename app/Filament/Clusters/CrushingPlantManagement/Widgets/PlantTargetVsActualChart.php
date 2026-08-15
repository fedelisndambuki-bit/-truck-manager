<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Widgets;

use App\Services\Quarry\CrushingPlantAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PlantTargetVsActualChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Target vs Actual (Today)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(CrushingPlantAnalyticsService::class)->targetVsActualByPlant($companyId);
            $labels = array_keys($raw['target'] ?? []);
            $target = array_values($raw['target'] ?? []);
            $actual = array_values($raw['actual'] ?? []);
        } catch (\Exception $e) {
            $labels = [];
            $target = [];
            $actual = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Target (t)', 'data' => $target, 'backgroundColor' => '#9ca3af'],
                ['label' => 'Actual (t)', 'data' => $actual, 'backgroundColor' => '#10b981'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
