<?php

namespace App\Filament\Widgets;

use App\Models\Staff;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrHeadcountByEmploymentTypeChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Headcount by Employment Type';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = Staff::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('employment_type, count(*) as total')
            ->groupBy('employment_type')
            ->pluck('total', 'employment_type');

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Staff'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Staff',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => ['#2f4f4f', '#4d7c7c', '#8fb8b8', '#c9e0e0'],
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
