<?php

namespace App\Filament\Widgets;

use App\Models\LeaveRequest;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrLeaveByTypeChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Leave Requests by Type';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = LeaveRequest::withoutGlobalScope('company')
            ->when($companyId, fn ($q) => $q->where('leave_requests.company_id', $companyId))
            ->join('leave_types', 'leave_requests.leave_type_id', '=', 'leave_types.id')
            ->selectRaw('leave_types.name as type_name, count(*) as total')
            ->groupBy('leave_types.name')
            ->pluck('total', 'type_name');

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Requests'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Requests',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => '#4d7c7c',
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
