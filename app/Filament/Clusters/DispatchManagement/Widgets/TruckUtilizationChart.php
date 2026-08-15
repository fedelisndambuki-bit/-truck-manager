<?php

namespace App\Filament\Clusters\DispatchManagement\Widgets;

use App\Services\Quarry\DispatchAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TruckUtilizationChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Truck Utilization (7 Days, trips)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(DispatchAnalyticsService::class)->truckUtilization($companyId);
            $labels = array_keys($raw);
            $values = array_map('intval', array_values($raw));
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
            'datasets' => [['label' => 'Trips', 'data' => $values, 'backgroundColor' => '#8b5cf6']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
