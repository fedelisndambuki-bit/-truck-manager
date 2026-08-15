<?php

namespace App\Filament\Clusters\Procurement\Widgets;

use App\Services\ProcurementAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class PurchaseTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Purchase Requisition Trend (14 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(ProcurementAnalyticsService::class)->purchaseTrend($companyId);
        } catch (\Exception $e) {
            $raw = [];
        }

        if (empty($raw)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'borderColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Requisitions', 'data' => array_values($raw), 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#8b5cf6', 'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
            ]],
            'labels' => array_keys($raw),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
