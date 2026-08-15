<?php

namespace App\Filament\Clusters\WorkflowApprovals\Widgets;

use App\Services\WorkflowAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class WorkflowUsageChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Workflow Usage';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $usage = app(WorkflowAnalyticsService::class)->workflowUsage($companyId);
        } catch (\Exception $e) {
            $usage = [];
        }

        if (empty($usage)) {
            return [
                'datasets' => [['label' => 'No Requests Yet', 'data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Requests', 'data' => array_values($usage), 'backgroundColor' => '#6366f1']],
            'labels' => array_keys($usage),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
