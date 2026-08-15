<?php

namespace App\Filament\Widgets;

use App\Models\SafetyIncident;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyMonthlyIncidentsChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly Incidents';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $labels = [];
        $data = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $data[] = SafetyIncident::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->count();
        }

        return [
            'datasets' => [[
                'label' => 'Incidents',
                'data' => $data,
                'borderColor' => '#dc2626',
                'backgroundColor' => 'rgba(220,38,38,0.1)',
                'fill' => true,
                'tension' => 0.3,
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
