<?php

namespace App\Filament\Clusters\BusinessIntelligence\Widgets;

use App\Models\Project;
use App\Services\Construction\ProjectFinanceService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CashFlowTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Cash Flow Trend (6 Months)';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;
        $finance = app(ProjectFinanceService::class);

        try {
            $projects = Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->get();

            $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i));
            $cashIn = $months->map(function ($month) use ($projects, $finance) {
                $from = $month->copy()->startOfMonth();
                $to = $month->copy()->endOfMonth();

                return round($projects->sum(fn (Project $p) => $finance->cashIn($p, $from, $to)), 2);
            });
            $cashOut = $months->map(function ($month) use ($projects, $finance) {
                $from = $month->copy()->startOfMonth();
                $to = $month->copy()->endOfMonth();

                return round($projects->sum(fn (Project $p) => $finance->cashOut($p, $from, $to)), 2);
            });

            $labels = $months->map(fn ($m) => $m->format('M Y'))->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $cashIn = collect();
            $cashOut = collect();
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Cash In', 'data' => $cashIn->toArray(), 'backgroundColor' => '#10b981'],
                ['label' => 'Cash Out', 'data' => $cashOut->toArray(), 'backgroundColor' => '#ef4444'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
