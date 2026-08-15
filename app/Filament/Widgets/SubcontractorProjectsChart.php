<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use App\Models\Subcontractor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SubcontractorProjectsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Projects by Subcontractor';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $subcontractors = Subcontractor::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->withCount(['contracts as project_count' => function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw('count(distinct project_id)'));
            }])
            ->get()
            ->filter(fn (Subcontractor $s) => $s->project_count > 0)
            ->sortByDesc('project_count')
            ->take(8);

        if ($subcontractors->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Contracts Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Projects Engaged',
                'data' => $subcontractors->pluck('project_count')->map(fn ($v) => (int) $v)->toArray(),
                'backgroundColor' => '#7c3aed',
            ]],
            'labels' => $subcontractors->pluck('company_name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
