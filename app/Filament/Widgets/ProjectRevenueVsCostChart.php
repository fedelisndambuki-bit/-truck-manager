<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Project;
use App\Services\Analytics\RevenueMetricsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProjectRevenueVsCostChart extends ChartWidget
{
    public ?Project $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Revenue vs Cost';

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
            $revenueByMonth = app(RevenueMetricsService::class)
                ->paidPaymentsQuery(null, $this->record->id)
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('created_at', 'month')), DB::raw('sum(amount) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $costByMonth = Expense::query()
                ->where('project_id', $this->record->id)
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('created_at', 'month')), DB::raw('sum(amount) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $labels = array_unique(array_merge(array_keys($revenueByMonth), array_keys($costByMonth)));
            sort($labels);

            $revenueValues = array_map(fn ($m) => (float) ($revenueByMonth[$m] ?? 0), $labels);
            $costValues = array_map(fn ($m) => (float) ($costByMonth[$m] ?? 0), $labels);
        } catch (\Exception $e) {
            $labels = [];
            $revenueValues = [];
            $costValues = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
                    'data' => $revenueValues,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
                [
                    'label' => 'Cost',
                    'data' => $costValues,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
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
