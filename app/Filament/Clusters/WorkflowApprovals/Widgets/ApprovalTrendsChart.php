<?php

namespace App\Filament\Clusters\WorkflowApprovals\Widgets;

use App\Services\WorkflowAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ApprovalTrendsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Approval Trends (6 Months)';

    protected static ?int $sort = 1;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $trend = app(WorkflowAnalyticsService::class)->approvalTrends($companyId);
        } catch (\Exception $e) {
            $trend = ['labels' => [], 'values' => []];
        }

        if (empty($trend['labels'])) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [['label' => 'Requests Submitted', 'data' => $trend['values'], 'borderColor' => '#3b82f6', 'backgroundColor' => 'rgba(59,130,246,0.1)', 'fill' => true]],
            'labels' => $trend['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
