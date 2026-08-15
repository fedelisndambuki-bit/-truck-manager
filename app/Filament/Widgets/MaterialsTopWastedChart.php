<?php

namespace App\Filament\Widgets;

use App\Models\MaterialWastage;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MaterialsTopWastedChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top Wasted Materials';

    protected static ?int $sort = 9;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $rawData = MaterialWastage::withoutGlobalScope('company')
            ->when($companyId, fn ($q) => $q->where('material_wastages.company_id', $companyId))
            ->join('materials', 'materials.id', '=', 'material_wastages.material_id')
            ->selectRaw('materials.name as material_name, sum(material_wastages.quantity) as total')
            ->groupBy('materials.name')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'material_name');

        if ($rawData->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Wastage Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Quantity Wasted',
                'data' => $rawData->map(fn ($v) => (float) $v)->values()->toArray(),
                'backgroundColor' => '#dc2626',
            ]],
            'labels' => $rawData->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
