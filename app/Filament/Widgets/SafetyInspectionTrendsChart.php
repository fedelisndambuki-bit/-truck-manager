<?php

namespace App\Filament\Widgets;

use App\Models\SafetyInspection;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyInspectionTrendsChart extends ChartWidget
{
    protected static ?string $heading = 'Inspection Trends';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $labels = [];
        $passed = [];
        $failed = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');

            $base = SafetyInspection::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month);

            $passed[] = (clone $base)->whereIn('result', ['Pass', 'Pass with Observations'])->count();
            $failed[] = (clone $base)->where('result', 'Fail')->count();
        }

        return [
            'datasets' => [
                ['label' => 'Passed', 'data' => $passed, 'borderColor' => '#22c55e', 'backgroundColor' => 'rgba(34,197,94,0.1)', 'fill' => true, 'tension' => 0.3],
                ['label' => 'Failed', 'data' => $failed, 'borderColor' => '#dc2626', 'backgroundColor' => 'rgba(220,38,38,0.1)', 'fill' => true, 'tension' => 0.3],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
