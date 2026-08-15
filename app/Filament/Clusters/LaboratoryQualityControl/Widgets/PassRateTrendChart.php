<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Widgets;

use App\Services\Quarry\LabAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PassRateTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Pass Rate Trend (14 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(LabAnalyticsService::class)->passRateTrend($companyId);
            $labels = array_keys($raw);
            $values = array_map(fn ($v) => $v === null ? null : (float) $v, array_values($raw));
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
            'datasets' => [['label' => 'Pass Rate %', 'data' => $values, 'borderColor' => '#10b981', 'backgroundColor' => 'rgba(16,185,129,0.1)']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
