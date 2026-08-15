<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\Stockpile;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class StockpileLevelsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Stockpile Levels by Material';

    protected static ?int $sort = 15;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = Stockpile::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->with('material')
                ->get()
                ->groupBy(fn (Stockpile $s) => $s->material?->name ?? 'Unknown')
                ->map(fn ($group) => $group->sum('quantity_available'));

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
                [
                    'label' => 'Available Tonnes',
                    'data' => $values,
                    'backgroundColor' => '#14b8a6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
