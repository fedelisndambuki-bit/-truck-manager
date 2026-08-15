<?php

namespace App\Filament\Widgets;

use App\Models\Expense;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ExpenseChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    protected static ?string $maxHeight = '200px';

    protected static ?string $heading = 'Expense Breakdown';

    protected static ?string $pollingInterval = '15s';

    protected function getData(): array
    {
        try {
            $companyId = auth()->user()?->company_id;
            $query = Expense::query()
                ->select('category', DB::raw('SUM(amount) as total'))
                ->groupBy('category');
            if ($companyId) {
                $query->where('company_id', $companyId);
            }
            $rawData = $query->pluck('total', 'category')->toArray();

            // Ensure categories and totals are clean, simple arrays
            $categories = array_keys($rawData);
            $totals = array_map('floatval', array_values($rawData));

        } catch (\Exception $e) {
            $categories = [];
            $totals = [];
        }

        // 1. Fallback for empty data to prevent Livewire serialization errors
        if (empty($categories)) {
            return [
                'datasets' => [
                    [
                        'label' => 'No Expenses Recorded',
                        'data' => [0], // Use 0 instead of 1 for a cleaner "empty" state
                        'backgroundColor' => ['#9ca3af'],
                    ],
                ],
                'labels' => ['No Data Available'],
            ];
        }

        // 2. Return data with explicit array_values to ensure indices are 0, 1, 2...
        return [
            'datasets' => [
                [
                    'label' => 'Expenses by Category',
                    'data' => $totals,
                    'backgroundColor' => [
                        '#ef4444', // Red
                        '#f59e0b', // Amber
                        '#3b82f6', // Blue
                        '#10b981', // Green
                        '#8b5cf6', // Purple
                        '#ec4899', // Pink
                    ],
                ],
            ],
            'labels' => $categories,
        ];
    }

    protected function getType(): string
    {
        return 'pie';
    }
}
