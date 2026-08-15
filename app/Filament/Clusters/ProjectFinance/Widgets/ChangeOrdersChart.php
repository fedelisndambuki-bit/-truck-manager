<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\ChangeOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

/**
 * Analytics: approved vs pending change-order cost impact per project.
 */
class ChangeOrdersChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Change Orders — Cost Impact';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $orders = ChangeOrder::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->with('project')
                ->get()
                ->groupBy(fn (ChangeOrder $co) => $co->project?->name ?? 'Unassigned');

            $labels = [];
            $approved = [];
            $pending = [];

            foreach ($orders->take(8) as $projectName => $group) {
                $labels[] = str($projectName)->limit(14)->toString();
                $approved[] = round((float) $group->where('status', 'Approved')->sum('cost_impact'), 2);
                $pending[] = round((float) $group->whereIn('status', ['Draft', 'Submitted', 'Under Review'])->sum('cost_impact'), 2);
            }
        } catch (\Exception $e) {
            $labels = [];
            $approved = [];
            $pending = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No change orders yet'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Approved', 'data' => $approved, 'backgroundColor' => '#10b981'],
                ['label' => 'Pending', 'data' => $pending, 'backgroundColor' => '#f59e0b'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
