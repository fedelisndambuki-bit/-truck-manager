<?php

namespace App\Filament\Widgets;

use App\Models\Hazard;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyHazardsBySiteChart extends ChartWidget
{
    protected static ?string $heading = 'Hazards by Site';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = Hazard::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotIn('status', ['Closed'])
            ->with('site:id,name')
            ->get()
            ->groupBy(fn ($hazard) => $hazard->site?->name ?? 'Unassigned')
            ->map->count()
            ->sortDesc()
            ->take(8);

        if ($counts->isEmpty()) {
            return ['datasets' => [['label' => 'Open Hazards', 'data' => [0], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['No Data']];
        }

        return [
            'datasets' => [[
                'label' => 'Open Hazards',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => '#f59e0b',
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
