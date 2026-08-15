<?php

namespace App\Filament\Widgets;

use App\Models\MaterialConsumption;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiaryMaterialConsumptionChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Material Consumption';

    protected function getData(): array
    {
        try {
            $companyId = Auth::user()?->company_id;

            $rawData = MaterialConsumption::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(30))
                ->select(DB::raw('date(date) as day'), DB::raw('sum(quantity_used) as total_used'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total_used', 'day')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Week 1', 'Week 2', 'Week 3'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quantity Consumed',
                    'data' => $values,
                    'backgroundColor' => '#8b5cf6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
