<?php

namespace App\Filament\Widgets;

use App\Models\DailySiteDiary;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DiaryProjectProgressChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Project Progress';

    protected function getData(): array
    {
        try {
            $companyId = Auth::user()?->company_id;

            $rows = DailySiteDiary::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('project_id')
                ->with('project:id,name')
                ->select('project_id', 'progress_pct')
                ->whereIn('id', function ($query) use ($companyId) {
                    $query->selectRaw('max(id)')
                        ->from('daily_site_diaries')
                        ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                        ->whereNotNull('project_id')
                        ->groupBy('project_id');
                })
                ->get();

            $labels = $rows->map(fn ($r) => $r->project?->name ?? 'Unknown')->toArray();
            $values = $rows->map(fn ($r) => (float) ($r->progress_pct ?? 0))->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Project A', 'Project B', 'Project C'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Latest Progress %',
                    'data' => $values,
                    'backgroundColor' => '#0ea5e9',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
