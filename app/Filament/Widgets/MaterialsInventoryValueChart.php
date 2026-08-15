<?php

namespace App\Filament\Widgets;

use App\Models\Material;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MaterialsInventoryValueChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Inventory Value by Category';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $materials = Material::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('category')
            ->get()
            ->groupBy(fn (Material $m) => $m->category?->name ?? 'Uncategorized')
            ->map(fn ($group) => $group->sum(fn (Material $m) => $m->inventory_value));

        if ($materials->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Materials'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Inventory Value',
                'data' => $materials->values()->toArray(),
                'backgroundColor' => '#2f4f4f',
            ]],
            'labels' => $materials->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
