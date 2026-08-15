<?php

namespace App\Filament\Widgets;

use App\Services\Analytics\RevenueMetricsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected int | string | array $columnSpan = 1;

    protected static ?string $heading = 'Weekly Revenue Tracker';

    protected static ?string $pollingInterval = '15s';

    protected function getData(): array
    {
        try {
            // 1. Fetch revenue data for the last 7 days (Paid payments only,
            // scoped to the current company — see RevenueMetricsService)
            $rawData = app(RevenueMetricsService::class)
                ->paidPaymentsQuery(Auth::user()?->company_id)
                ->select(DB::raw('date(created_at) as date'), DB::raw('sum(amount) as total'))
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->pluck('total', 'date')
                ->toArray();

            // 2. Ensure we have clean, numeric arrays (Livewire-safe)
            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));

        } catch (\Exception $e) {
            // Fallback to empty arrays if database query fails
            $labels = [];
            $values = [];
        }

        // 3. If no data, provide a placeholder trend line so the chart doesn't crash
        if (empty($values)) {
            return [
                'datasets' => [
                    [
                        'label' => 'Waiting for Data',
                        'data' => [0, 0, 0, 0, 0, 0, 0],
                        'borderColor' => '#9ca3af',
                    ],
                ],
                'labels' => ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (KES)',
                    'data' => $values,
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
