<?php

namespace App\Filament\Widgets;

use App\Models\Hazard;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyRiskLevelsChart extends ChartWidget
{
    protected static ?string $heading = 'Risk Levels (Hazard Register)';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = Hazard::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('risk_rating, count(*) as total')
            ->groupBy('risk_rating')
            ->pluck('total', 'risk_rating');

        if ($counts->isEmpty()) {
            return ['datasets' => [['label' => 'Hazards', 'data' => [0], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['No Data']];
        }

        $colorMap = ['Low' => '#22c55e', 'Medium' => '#eab308', 'High' => '#f97316', 'Critical' => '#dc2626'];

        return [
            'datasets' => [[
                'label' => 'Hazards',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => collect($counts->keys())->map(fn ($k) => $colorMap[$k] ?? '#9ca3af')->toArray(),
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
