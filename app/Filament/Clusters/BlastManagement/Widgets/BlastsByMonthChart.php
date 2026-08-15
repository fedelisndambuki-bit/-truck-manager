<?php

namespace App\Filament\Clusters\BlastManagement\Widgets;

use App\Services\Quarry\BlastAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class BlastsByMonthChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Blasts by Month';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(BlastAnalyticsService::class)->blastsByMonth($companyId);
            $labels = array_keys($raw);
            $values = array_values($raw);
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
            'datasets' => [['label' => 'Blasts', 'data' => $values, 'backgroundColor' => '#f97316']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
