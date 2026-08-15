<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Widgets;

use App\Services\Quarry\EamAnalyticsService;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class WorkshopPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Workshop Performance — Jobs Closed by Technician';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = app(EamAnalyticsService::class)->workshopPerformanceByTechnician($companyId);
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
            'datasets' => [[
                'label' => 'Jobs Closed', 'data' => $values, 'backgroundColor' => '#3b82f6',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
