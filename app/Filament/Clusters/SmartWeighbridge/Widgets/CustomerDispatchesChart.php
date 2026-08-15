<?php

namespace App\Filament\Clusters\SmartWeighbridge\Widgets;

use App\Services\Quarry\WeighbridgeAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CustomerDispatchesChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Customer Dispatches (30 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(WeighbridgeAnalyticsService::class)->customerDispatches($companyId);
            $labels = array_keys($raw);
            $values = array_map('floatval', array_values($raw));
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
            'datasets' => [['label' => 'Tonnes', 'data' => $values, 'backgroundColor' => '#06b6d4']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
