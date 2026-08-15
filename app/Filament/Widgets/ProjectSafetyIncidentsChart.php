<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\SafetyIncident;
use Filament\Widgets\ChartWidget;

class ProjectSafetyIncidentsChart extends ChartWidget
{
    public ?Project $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Safety Incidents';

    public function mount(?Project $record = null): void
    {
        $this->record = $record;
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        try {
            $siteIds = $this->record->sites()->pluck('id');

            $rawData = SafetyIncident::query()
                ->whereIn('site_id', $siteIds)
                ->selectRaw('severity, count(*) as total')
                ->groupBy('severity')
                ->pluck('total', 'severity')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'No Incidents', 'data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data Available'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Incidents by Severity',
                    'data' => $values,
                    'backgroundColor' => ['#16a34a', '#f59e0b', '#ef4444', '#991b1b'],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
