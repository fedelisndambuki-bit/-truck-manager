<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Project;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class BudgetUtilizationChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '200px';

    protected static ?string $heading = 'Budget Utilization';

    protected static ?int $sort = 14;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $totalBudget = (float) Project::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->sum('budget');
            $spent = (float) Expense::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('project_id')
                ->sum('amount');
            $remaining = max(0, $totalBudget - $spent);
        } catch (\Exception $e) {
            $spent = 0;
            $remaining = 0;
        }

        if ($spent <= 0 && $remaining <= 0) {
            return [
                'datasets' => [['label' => 'No Budget Data', 'data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data Available'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Budget',
                    'data' => [$spent, $remaining],
                    'backgroundColor' => ['#f59e0b', '#10b981'],
                ],
            ],
            'labels' => ['Spent', 'Remaining'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
