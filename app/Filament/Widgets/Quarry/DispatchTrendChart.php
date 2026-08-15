<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\WeighbridgeTicket;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DispatchTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Dispatch Trend (14 Days)';

    protected static ?int $sort = 12;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = WeighbridgeTicket::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('direction', 'Outbound')
                ->where('created_at', '>=', now()->subDays(14))
                ->select(DB::raw("strftime('%Y-%m-%d', created_at) as day"), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('intval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($values)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Outbound Dispatches',
                    'data' => $values,
                    'backgroundColor' => '#3b82f6',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
