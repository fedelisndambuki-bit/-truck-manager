<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Widgets;

use App\Services\Quarry\LabAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CustomerComplaintsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Customer Complaints (30 Days)';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(LabAnalyticsService::class)->customerComplaintsTrend($companyId);
            $labels = array_keys($raw);
            $values = array_map('intval', array_values($raw));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Complaints', 'data' => $values, 'backgroundColor' => '#f97316']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
