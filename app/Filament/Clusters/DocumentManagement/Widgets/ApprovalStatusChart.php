<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Models\DocumentApproval;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ApprovalStatusChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Approval Status';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $counts = DocumentApproval::query()
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

        $colors = [
            'Pending' => '#f59e0b', 'Approved' => '#10b981',
            'Rejected' => '#ef4444', 'Revision Required' => '#ef4444',
        ];

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
