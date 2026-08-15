<?php

namespace App\Filament\Widgets;

use App\Models\SafetyIncident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrSafetyIncidentsBySeverityChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Incidents by Severity';

    protected static ?int $sort = 8;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = SafetyIncident::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('severity, count(*) as total')
            ->groupBy('severity')
            ->pluck('total', 'severity');

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Incidents'],
            ];
        }

        $colorMap = ['Low' => '#22c55e', 'Medium' => '#eab308', 'High' => '#f97316', 'Critical' => '#dc2626'];

        return [
            'datasets' => [[
                'label' => 'Incidents',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => collect($counts->keys())->map(fn ($k) => $colorMap[$k] ?? '#9ca3af')->toArray(),
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
