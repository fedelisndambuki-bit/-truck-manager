<?php

namespace App\Filament\Clusters\BlastManagement\Widgets;

use App\Services\Quarry\BlastAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class BlastPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Blast Performance (Outcome Mix)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(BlastAnalyticsService::class)->blastPerformanceBreakdown($companyId);
            $labels = array_keys($raw);
            $values = array_values($raw);
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
                'label' => 'Blasts', 'data' => $values,
                'backgroundColor' => ['#10b981', '#f59e0b', '#ef4444', '#6366f1'],
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
