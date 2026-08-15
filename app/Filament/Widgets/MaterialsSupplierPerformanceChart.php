<?php

namespace App\Filament\Widgets;

use App\Models\Supplier;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class MaterialsSupplierPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Supplier Performance';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $suppliers = Supplier::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('performance_rating')
            ->orderByDesc('performance_rating')
            ->limit(8)
            ->get();

        if ($suppliers->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Ratings Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Performance Rating',
                'data' => $suppliers->pluck('performance_rating')->map(fn ($v) => (float) $v)->toArray(),
                'backgroundColor' => '#4d7c7c',
            ]],
            'labels' => $suppliers->pluck('company_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
