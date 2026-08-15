<?php

namespace App\Filament\Widgets;

use App\Models\DailySiteDiary;
use App\Models\Site;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class SiteProgressChart extends ChartWidget
{
    public ?Site $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Site Progress';

    public function mount(?Site $record = null): void
    {
        $this->record = $record;
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        try {
            $rawData = DailySiteDiary::query()
                ->where('site_id', $this->record->id)
                ->where('date', '>=', now()->subDays(30))
                ->select(DB::raw('date(date) as day'), DB::raw('count(*) as total'))
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

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Week 1', 'Week 2', 'Week 3'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Site Reports Logged',
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
