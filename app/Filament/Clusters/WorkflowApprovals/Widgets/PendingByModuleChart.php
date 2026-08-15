<?php

namespace App\Filament\Clusters\WorkflowApprovals\Widgets;

use App\Services\WorkflowAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PendingByModuleChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Pending by Module';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $pending = app(WorkflowAnalyticsService::class)->pendingByModule($companyId);
        } catch (\Exception $e) {
            $pending = [];
        }

        if (empty($pending)) {
            return [
                'datasets' => [['label' => 'No Pending Requests', 'data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data'],
            ];
        }

        $palette = ['#3b82f6', '#f59e0b', '#10b981', '#ef4444', '#8b5cf6', '#0ea5e9', '#ec4899'];

        return [
            'datasets' => [['label' => 'Pending', 'data' => array_values($pending), 'backgroundColor' => array_slice($palette, 0, count($pending))]],
            'labels' => array_keys($pending),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
