<?php

namespace App\Filament\Clusters\ProductionManagement\Widgets;

use App\Models\ProductionLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class YieldAnalysisChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Yield Analysis (30 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = ProductionLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'Approved')
                ->whereNotNull('yield_percent')
                ->where('date', '>=', now()->subDays(30))
                ->select(DB::raw(\App\Support\SqlDateFormat::daily('date', 'day')), DB::raw('avg(yield_percent) as avg_yield'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('avg_yield', 'day')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($values)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'borderColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Yield %',
                    'data' => $values,
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
