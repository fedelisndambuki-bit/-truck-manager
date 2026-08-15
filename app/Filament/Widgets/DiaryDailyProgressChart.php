<?php

namespace App\Filament\Widgets;

use App\Models\DailySiteDiary;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiaryDailyProgressChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Daily Progress';

    protected function getData(): array
    {
        try {
            $companyId = Auth::user()?->company_id;

            $rawData = DailySiteDiary::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(30))
                ->select(DB::raw('date(date) as day'), DB::raw('avg(progress_pct) as avg_progress'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('avg_progress', 'day')
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
                    'label' => 'Avg. Progress %',
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
