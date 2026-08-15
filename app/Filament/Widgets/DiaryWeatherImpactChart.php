<?php

namespace App\Filament\Widgets;

use App\Models\DailySiteDiary;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DiaryWeatherImpactChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Weather Impact';

    protected function getData(): array
    {
        try {
            $companyId = Auth::user()?->company_id;

            $counts = DailySiteDiary::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(30))
                ->whereNotNull('weather_impact')
                ->selectRaw('weather_impact, count(*) as total')
                ->groupBy('weather_impact')
                ->pluck('total', 'weather_impact');
        } catch (\Exception $e) {
            $counts = collect();
        }

        $levels = ['None', 'Minor', 'Moderate', 'Severe'];
        $values = collect($levels)->map(fn ($level) => (float) ($counts[$level] ?? 0));

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0], 'backgroundColor' => ['#9ca3af', '#9ca3af', '#9ca3af', '#9ca3af']]],
                'labels' => $levels,
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Diaries',
                    'data' => $values->toArray(),
                    'backgroundColor' => ['#10b981', '#f59e0b', '#f97316', '#ef4444'],
                ],
            ],
            'labels' => $levels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
