<?php

namespace App\Filament\Widgets;

use App\Models\MaterialConsumption;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialsCostTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Material Cost Trend';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = MaterialConsumption::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subMonths(6))
                ->select(DB::raw("strftime('%Y-%m', date) as month"), DB::raw('sum(cost) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();
        } catch (\Exception $e) {
            $rawData = [];
        }

        if (empty($rawData)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Consumption Cost',
                'data' => array_map('floatval', array_values($rawData)),
                'borderColor' => '#b45309',
                'backgroundColor' => 'rgba(180,83,9,0.1)',
            ]],
            'labels' => array_keys($rawData),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
