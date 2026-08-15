<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use App\Models\Project;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProjectLabourProductivityChart extends ChartWidget
{
    public ?Project $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Labour Productivity';

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

            $present = Attendance::query()
                ->whereIn('site_id', $siteIds)
                ->where('status', 'Present')
                ->where('date', '>=', now()->subDays(7))
                ->select(DB::raw('date(date) as day'), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $absent = Attendance::query()
                ->whereIn('site_id', $siteIds)
                ->where('status', 'Absent')
                ->where('date', '>=', now()->subDays(7))
                ->select(DB::raw('date(date) as day'), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $labels = array_keys($present);
            $values = [];
            foreach ($labels as $day) {
                $p = (float) ($present[$day] ?? 0);
                $a = (float) ($absent[$day] ?? 0);
                $values[] = ($p + $a) > 0 ? round(($p / ($p + $a)) * 100) : 0;
            }
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Attendance Rate %',
                    'data' => $values,
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
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
