<?php

namespace App\Filament\Widgets;

use App\Models\Attendance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HrAttendanceTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Attendance Trend (Last 14 Days)';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = Attendance::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(14))
                ->where('status', 'Present')
                ->select(DB::raw('date'), DB::raw('count(*) as total'))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date')
                ->toArray();
        } catch (\Exception $e) {
            $rawData = [];
        }

        if (empty($rawData)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Day 1', 'Day 2', 'Day 3', 'Day 4', 'Day 5'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Present',
                'data' => array_values($rawData),
                'borderColor' => '#2f4f4f',
                'backgroundColor' => 'rgba(47,79,79,0.1)',
            ]],
            'labels' => array_keys($rawData),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
