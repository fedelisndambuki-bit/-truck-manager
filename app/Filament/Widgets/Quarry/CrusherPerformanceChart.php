<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\CrushingPlantLog;
use App\Models\ProductionRun;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CrusherPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Crusher Output (14 Days)';

    protected static ?int $sort = 14;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            // CrushingPlantLog has no live write path in this app (seeder-only) —
            // ProductionRun is the model actually credited by real approvals, so
            // both are merged into the same daily totals here.
            $logData = CrushingPlantLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(14))
                ->select(DB::raw(\App\Support\SqlDateFormat::daily('date', 'day')), DB::raw('sum(output_tonnes) as total'))
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $runData = ProductionRun::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereIn('status', ['Approved', 'Completed'])
                ->where('start_time', '>=', now()->subDays(14))
                ->select(DB::raw(\App\Support\SqlDateFormat::daily('start_time', 'day')), DB::raw('sum(output_quantity) as total'))
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $rawData = [];
            foreach ([$logData, $runData] as $source) {
                foreach ($source as $day => $total) {
                    $rawData[$day] = ($rawData[$day] ?? 0) + (float) $total;
                }
            }
            ksort($rawData);

            $labels = array_keys($rawData);
            $values = array_values($rawData);
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($values)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'borderColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Output Tonnes',
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
