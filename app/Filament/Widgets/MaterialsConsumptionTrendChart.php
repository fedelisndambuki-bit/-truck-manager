<?php

namespace App\Filament\Widgets;

use App\Models\MaterialConsumption;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialsConsumptionTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Material Consumption (Last 14 Days)';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = MaterialConsumption::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subDays(14))
                ->select(DB::raw('date'), DB::raw('sum(quantity_used) as total'))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date')
                ->toArray();
        } catch (\Exception $e) {
            $rawData = [];
        }

        if (empty($rawData)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['—', '—', '—'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Quantity Used',
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
