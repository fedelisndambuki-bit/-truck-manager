<?php

namespace App\Filament\Clusters\WorkflowApprovals\Widgets;

use App\Services\WorkflowAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DepartmentActivityChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Department Activity';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $activity = app(WorkflowAnalyticsService::class)->departmentActivity($companyId);
        } catch (\Exception $e) {
            $activity = [];
        }

        if (empty($activity)) {
            return [
                'datasets' => [['label' => 'No Requests Yet', 'data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Requests', 'data' => array_values($activity), 'backgroundColor' => '#14b8a6']],
            'labels' => array_keys($activity),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
