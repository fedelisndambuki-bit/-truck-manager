<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectBudgetVsActualChart extends ChartWidget
{
    public ?Project $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Budget vs Actual';

    public function mount(?Project $record = null): void
    {
        $this->record = $record;
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        try {
            $budget = (float) ($this->record->budget ?? 0);
            $spent = (float) Expense::query()->where('project_id', $this->record->id)->sum('amount');
        } catch (\Exception $e) {
            $budget = 0;
            $spent = 0;
        }

        return [
            'datasets' => [
                [
                    'label' => 'Amount',
                    'data' => [$budget, $spent],
                    'backgroundColor' => ['#2563eb', '#f59e0b'],
                ],
            ],
            'labels' => ['Budget', 'Actual Spent'],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
