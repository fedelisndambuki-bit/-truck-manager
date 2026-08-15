<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\DispatchOrder;
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
            // WeighbridgeTicket (IoT hardware feed) and DispatchOrder (Smart
            // Weighbridge / Dispatch Management workflow) are two
            // unreconciled live write paths onto the same real dispatches —
            // both must be counted or the trend understates whichever route
            // a given truck went through.
            $ticketData = WeighbridgeTicket::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('direction', 'Outbound')
                ->where('created_at', '>=', now()->subDays(14))
                ->select(DB::raw(\App\Support\SqlDateFormat::daily('created_at', 'day')), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $orderData = DispatchOrder::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', now()->subDays(14))
                ->select(DB::raw(\App\Support\SqlDateFormat::daily('created_at', 'day')), DB::raw('count(*) as total'))
                ->groupBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $rawData = [];
            foreach ([$ticketData, $orderData] as $source) {
                foreach ($source as $day => $total) {
                    $rawData[$day] = ($rawData[$day] ?? 0) + (int) $total;
                }
            }
            ksort($rawData);

            $labels = array_keys($rawData);
            $values = array_values($rawData);
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
