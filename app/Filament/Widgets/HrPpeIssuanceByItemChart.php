<?php

namespace App\Filament\Widgets;

use App\Models\PpeIssuance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrPpeIssuanceByItemChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'PPE Issued by Item';

    protected static ?int $sort = 10;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = PpeIssuance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('item, count(*) as total')
            ->groupBy('item')
            ->pluck('total', 'item');

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Issuances'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Issued',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => '#c9e0e0',
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
