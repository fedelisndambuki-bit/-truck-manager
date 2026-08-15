<?php

namespace App\Filament\Widgets;

use App\Models\SafetyIncident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyIncidentTypesChart extends ChartWidget
{
    protected static ?string $heading = 'Incident Types';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('incident_category')
            ->selectRaw('incident_category, count(*) as total')
            ->groupBy('incident_category')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'incident_category');

        if ($counts->isEmpty()) {
            return ['datasets' => [['label' => 'Incidents', 'data' => [0], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['No Data']];
        }

        return [
            'datasets' => [[
                'label' => 'Incidents',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => '#f97316',
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
