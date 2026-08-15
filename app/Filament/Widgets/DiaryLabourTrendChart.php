<?php

namespace App\Filament\Widgets;

use App\Models\DailySiteDiary;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DiaryLabourTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Labour Trend';

    protected function getData(): array
    {
        try {
            $companyId = Auth::user()?->company_id;

            $rawData = DailySiteDiary::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(30))
                ->select(DB::raw('date(date) as day'), DB::raw('sum(workers_on_site) as total_present'), DB::raw('sum(workers_absent) as total_absent'))
                ->groupBy('day')
                ->orderBy('day')
                ->get();

            $labels = $rawData->pluck('day')->toArray();
            $present = $rawData->pluck('total_present')->map(fn ($v) => (float) $v)->toArray();
            $absent = $rawData->pluck('total_absent')->map(fn ($v) => (float) $v)->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $present = [];
            $absent = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Week 1', 'Week 2', 'Week 3'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Present', 'data' => $present, 'borderColor' => '#3b82f6', 'backgroundColor' => 'rgba(59, 130, 246, 0.1)', 'tension' => 0.4],
                ['label' => 'Absent', 'data' => $absent, 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239, 68, 68, 0.1)', 'tension' => 0.4],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
