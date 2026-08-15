<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\RetentionEntry;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Analytics: retention held vs released per project.
 */
class RetentionChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Retention — Held vs Released';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $entries = RetentionEntry::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->with('project')
                ->get()
                ->groupBy(fn (RetentionEntry $r) => $r->project?->name ?? 'Unassigned');

            $labels = [];
            $held = [];
            $released = [];

            foreach ($entries->take(8) as $projectName => $group) {
                $labels[] = str($projectName)->limit(14)->toString();
                $held[] = round((float) $group->sum(fn (RetentionEntry $r) => (float) $r->held_amount - (float) $r->released_amount), 2);
                $released[] = round((float) $group->sum('released_amount'), 2);
            }
        } catch (\Exception $e) {
            $labels = [];
            $held = [];
            $released = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No retention data yet'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Outstanding Held', 'data' => $held, 'backgroundColor' => '#64748b'],
                ['label' => 'Released', 'data' => $released, 'backgroundColor' => '#10b981'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
