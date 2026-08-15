<?php

namespace App\Filament\Widgets;

use App\Models\Project;
use App\Models\QualityInspection;
use Filament\Widgets\ChartWidget;

class ProjectQualityIssuesChart extends ChartWidget
{
    public ?Project $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Quality Issues';

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

            $pass = QualityInspection::query()->whereIn('site_id', $siteIds)->where('result', 'Pass')->count();
            $fail = QualityInspection::query()->whereIn('site_id', $siteIds)->where('result', 'Fail')->count();
        } catch (\Exception $e) {
            $pass = 0;
            $fail = 0;
        }

        if ($pass <= 0 && $fail <= 0) {
            return [
                'datasets' => [['label' => 'No Data', 'data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data Available'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Quality Checks',
                    'data' => [$pass, $fail],
                    'backgroundColor' => ['#10b981', '#ef4444'],
                ],
            ],
            'labels' => ['Pass', 'Fail'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
