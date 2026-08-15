<?php

namespace App\Filament\Clusters\Procurement\Widgets;

use App\Services\ProcurementAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class BudgetConsumptionChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Budget Consumption (%)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(ProcurementAnalyticsService::class)->budgetConsumption($companyId);
        } catch (\Exception $e) {
            $raw = [];
        }

        if (empty($raw)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Consumed %', 'data' => array_values($raw),
                'backgroundColor' => array_map(fn ($v) => $v >= 90 ? '#ef4444' : ($v >= 70 ? '#f59e0b' : '#10b981'), array_values($raw)),
            ]],
            'labels' => array_keys($raw),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
