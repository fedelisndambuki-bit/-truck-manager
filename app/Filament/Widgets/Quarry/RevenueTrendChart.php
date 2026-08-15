<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevenueTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Revenue Trend (6 Months)';

    protected static ?int $sort = 11;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = Payment::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'Paid')
                ->where('payment_date', '>=', now()->subMonths(6))
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('payment_date', 'month')), DB::raw('sum(amount) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($values)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Revenue',
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
