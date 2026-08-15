<?php

namespace App\Filament\Clusters\ProductionManagement\Widgets;

use App\Models\ProductionLog;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ShiftPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Shift Performance (30 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = ProductionLog::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(30))
                ->get()
                ->groupBy('shift')
                ->map(fn ($group) => $group->sum('quantity_tonnes'));

            $labels = $rawData->keys()->toArray();
            $values = array_map('floatval', $rawData->values()->toArray());
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Tonnes Produced', 'data' => $values, 'backgroundColor' => '#3b82f6'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
