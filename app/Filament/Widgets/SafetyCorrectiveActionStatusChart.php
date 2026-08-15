<?php

namespace App\Filament\Widgets;

use App\Models\CorrectiveAction;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyCorrectiveActionStatusChart extends ChartWidget
{
    protected static ?string $heading = 'Corrective Action Status';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = CorrectiveAction::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($counts->isEmpty()) {
            return ['datasets' => [['label' => 'Corrective Actions', 'data' => [0], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['No Data']];
        }

        $colorMap = ['Open' => '#dc2626', 'In Progress' => '#eab308', 'Completed' => '#3b82f6', 'Verified' => '#6366f1', 'Closed' => '#22c55e'];

        return [
            'datasets' => [[
                'label' => 'Corrective Actions',
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
