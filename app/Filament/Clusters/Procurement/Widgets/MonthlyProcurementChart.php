<?php

namespace App\Filament\Clusters\Procurement\Widgets;

use App\Services\ProcurementAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MonthlyProcurementChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Monthly Procurement Spend (6 Months)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(ProcurementAnalyticsService::class)->monthlyProcurementTrend($companyId);
        } catch (\Exception $e) {
            $raw = [];
        }

        if (empty($raw) || array_sum($raw) === 0) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'borderColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Spend', 'data' => array_values($raw), 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#3b82f6', 'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
            ]],
            'labels' => array_keys($raw),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
