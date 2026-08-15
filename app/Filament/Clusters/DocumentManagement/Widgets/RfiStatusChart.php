<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Models\Rfi;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class RfiStatusChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'RFI Status';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $counts = Rfi::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->selectRaw('status, count(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status');
        } catch (\Exception $e) {
            $counts = collect();
        }

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['Waiting for Data'],
            ];
        }

        $colors = ['Open' => '#f59e0b', 'Answered' => '#2563eb', 'Closed' => '#10b981', 'Overdue' => '#ef4444'];

        return [
            'datasets' => [[
                'data' => $counts->values()->all(),
                'backgroundColor' => $counts->keys()->map(fn ($k) => $colors[$k] ?? '#64748b')->all(),
            ]],
            'labels' => $counts->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
