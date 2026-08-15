<?php

namespace App\Filament\Widgets;

use App\Models\Department;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrHeadcountByDepartmentChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Headcount by Department';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $departments = Department::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->withCount('staff')
            ->get();

        if ($departments->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Departments'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Staff',
                'data' => $departments->pluck('staff_count')->toArray(),
                'backgroundColor' => '#2f4f4f',
            ]],
            'labels' => $departments->pluck('name')->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
