<?php

namespace App\Filament\Clusters\BusinessIntelligence\Widgets;

use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CostTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Cost Trend (6 Months)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;
        $finance = app(ProjectFinanceService::class);

        try {
            $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();

            $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i));
            $values = $months->map(function ($month) use ($projects, $finance) {
                $from = $month->copy()->startOfMonth();
                $to = $month->copy()->endOfMonth();

                return round($projects->sum(fn (Project $p) => $finance->totalActualCost($p, $from, $to)), 2);
            });

            $labels = $months->map(fn ($m) => $m->format('M Y'))->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $values = collect();
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [['label' => 'Actual Cost', 'data' => $values->toArray(), 'borderColor' => '#ef4444', 'backgroundColor' => 'rgba(239,68,68,0.1)', 'fill' => true]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
