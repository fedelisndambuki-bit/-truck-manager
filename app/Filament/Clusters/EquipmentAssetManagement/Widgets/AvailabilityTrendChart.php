<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Widgets;

use App\Services\Quarry\EamAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AvailabilityTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Equipment Availability Trend (14 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(EamAnalyticsService::class)->availabilityTrend($companyId);
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
                'label' => 'Availability %', 'data' => $values, 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
