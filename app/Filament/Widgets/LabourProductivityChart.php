<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class LabourProductivityChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Labour Productivity (7-Day Attendance)';

    protected static ?int $sort = 16;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $present = Attendance::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'Present')
                ->where('date', '>=', now()->subDays(7))
                ->select(DB::raw('date(date) as day'), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $absent = Attendance::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'Absent')
                ->where('date', '>=', now()->subDays(7))
                ->select(DB::raw('date(date) as day'), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $labels = array_keys($present);
            $productivity = [];
            foreach ($labels as $day) {
                $p = (float) ($present[$day] ?? 0);
                $a = (float) ($absent[$day] ?? 0);
                $productivity[] = ($p + $a) > 0 ? round(($p / ($p + $a)) * 100) : 0;
            }
        } catch (\Exception $e) {
            $labels = [];
            $productivity = [];
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
                    'data' => $productivity,
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
