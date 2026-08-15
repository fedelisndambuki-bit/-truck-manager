<?php

namespace App\Filament\Clusters\ProductionManagement\Widgets;

use App\Models\CrushingPlantLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PlantPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Plant Performance (14 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = CrushingPlantLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(14))
                ->with('crushingPlant')
                ->get()
                ->groupBy(fn (CrushingPlantLog $log) => $log->crushingPlant?->name ?? 'Unknown')
                ->map(fn ($group) => $group->sum('output_tonnes'));

            $labels = $rawData->keys()->toArray();
            $values = array_map('floatval', $rawData->values()->toArray());
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
            'datasets' => [
                ['label' => 'Output Tonnes', 'data' => $values, 'backgroundColor' => '#8b5cf6'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
