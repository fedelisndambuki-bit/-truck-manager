<?php

namespace App\Filament\Widgets;

use App\Models\MaterialConsumption;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MaterialsTopConsumedChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top Consumed Materials';

    protected static ?int $sort = 8;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $rawData = MaterialConsumption::withoutGlobalScope('company')
            ->when($companyId, fn ($q) => $q->where('material_consumptions.company_id', $companyId))
            ->join('materials', 'materials.id', '=', 'material_consumptions.material_id')
            ->selectRaw('materials.name as material_name, sum(material_consumptions.quantity_used) as total')
            ->groupBy('materials.name')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'material_name');

        if ($rawData->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Consumption Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Quantity Used',
                'data' => $rawData->map(fn ($v) => (float) $v)->values()->toArray(),
                'backgroundColor' => '#2f4f4f',
            ]],
            'labels' => $rawData->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
