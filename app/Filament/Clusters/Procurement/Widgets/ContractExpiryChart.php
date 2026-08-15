<?php

namespace App\Filament\Clusters\Procurement\Widgets;

use App\Services\ProcurementAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class ContractExpiryChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Contract Expiry (Next 6 Months)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(ProcurementAnalyticsService::class)->contractExpiryTrend($companyId);
        } catch (\Exception $e) {
            $raw = [];
        }

        if (empty($raw) || array_sum($raw) === 0) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Expiring Contracts', 'data' => array_values($raw), 'backgroundColor' => '#ef4444']],
            'labels' => array_keys($raw),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
