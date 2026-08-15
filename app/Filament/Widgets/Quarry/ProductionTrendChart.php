<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\ProductionLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProductionTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Production Trend (14 Days)';

    protected static ?int $sort = 10;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = ProductionLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(14))
                ->select(DB::raw(\App\Support\SqlDateFormat::daily('date', 'day')), DB::raw('sum(quantity_tonnes) as total'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
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
                    'label' => 'Tonnes Produced',
                    'data' => $values,
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
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
