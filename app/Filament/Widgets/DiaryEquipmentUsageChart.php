<?php

namespace App\Filament\Widgets;

use App\Models\DailySiteDiary;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiaryEquipmentUsageChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Equipment Usage';

    protected function getData(): array
    {
        try {
            $companyId = Auth::user()?->company_id;

            $rawData = DailySiteDiary::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(30))
                ->select(DB::raw('date(date) as day'), DB::raw('avg(equipment_utilization_pct) as avg_utilization'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('avg_utilization', 'day')
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
                    'label' => 'Avg. Utilization %',
                    'data' => $values,
                    'backgroundColor' => '#f59e0b',
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
