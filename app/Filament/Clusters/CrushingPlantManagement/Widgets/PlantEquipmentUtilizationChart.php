<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Widgets;

use App\Services\Quarry\CrushingPlantAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PlantEquipmentUtilizationChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Equipment Utilization';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(CrushingPlantAnalyticsService::class)->equipmentUtilizationByType($companyId);
            $labels = array_keys($raw);
            $values = array_values($raw);
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels) || array_sum($values) <= 0) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Running Units', 'data' => $values, 'backgroundColor' => '#f59e0b']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
