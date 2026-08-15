<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Widgets;

use App\Services\Quarry\EamAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class EquipmentAgeAnalysisChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Equipment Age Analysis';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $buckets = app(EamAnalyticsService::class)->equipmentAgeAnalysis($companyId);
        } catch (\Exception $e) {
            $buckets = [];
        }

        if (empty($buckets) || array_sum($buckets) === 0) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Equipment Count', 'data' => array_values($buckets),
                'backgroundColor' => ['#10b981', '#3b82f6', '#f59e0b', '#f97316', '#ef4444'],
            ]],
            'labels' => array_keys($buckets),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
