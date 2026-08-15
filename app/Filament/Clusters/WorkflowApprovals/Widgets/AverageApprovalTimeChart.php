<?php

namespace App\Filament\Clusters\WorkflowApprovals\Widgets;

use App\Models\ApprovalRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class AverageApprovalTimeChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Average Approval Time (6 Months)';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $months = collect(range(5, 0))->map(fn (int $i) => now()->subMonths($i));

            $values = $months->map(function ($month) use ($companyId) {
                $completed = ApprovalRequest::query()
                    ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                    ->whereNotNull('completed_at')
                    ->whereNotNull('submitted_at')
                    ->whereYear('completed_at', $month->year)
                    ->whereMonth('completed_at', $month->month)
                    ->get();

                return $completed->isNotEmpty()
                    ? round($completed->avg(fn (ApprovalRequest $r) => $r->submitted_at->diffInHours($r->completed_at)), 1)
                    : 0;
            });

            $labels = $months->map(fn ($m) => $m->format('M Y'))->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $values = collect();
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [['label' => 'Avg Hours to Complete', 'data' => $values->toArray(), 'borderColor' => '#f59e0b', 'backgroundColor' => 'rgba(245,158,11,0.1)', 'fill' => true]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
