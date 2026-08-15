<?php

namespace App\Filament\Clusters\BusinessIntelligence\Widgets;

use App\Models\QualityInspection;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class QualityPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Quality Performance (6 Months)';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i));

            $rates = $months->map(function ($month) use ($companyId) {
                $inspections = QualityInspection::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereYear('date', $month->year)->whereMonth('date', $month->month)
                    ->get();

                return $inspections->isNotEmpty() ? round(($inspections->where('result', 'Pass')->count() / $inspections->count()) * 100, 1) : null;
            });

            $labels = $months->map(fn ($m) => $m->format('M Y'))->toArray();
            $values = $rates->map(fn ($v) => $v ?? 0)->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [['label' => 'Pass Rate (%)', 'data' => $values, 'borderColor' => '#0ea5e9', 'backgroundColor' => 'rgba(14,165,233,0.1)', 'fill' => true]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
