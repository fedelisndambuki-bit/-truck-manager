<?php

namespace App\Filament\Clusters\DispatchManagement\Widgets;

use App\Services\Quarry\DispatchAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TopCustomersChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top Customers (30 Days, tonnes)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(DispatchAnalyticsService::class)->topCustomers($companyId);
            $labels = array_keys($raw);
            $values = array_map('floatval', array_values($raw));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Tonnes',
                'data' => $values,
                'backgroundColor' => ['#3b82f6', '#f59e0b', '#8b5cf6', '#10b981', '#06b6d4', '#eab308', '#ef4444', '#84cc16', '#ec4899', '#14b8a6'],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
