<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class EquipmentUtilizationChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Equipment Utilization';

    protected static ?int $sort = 15;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = Equipment::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->withSum('allocations as total_hours', 'operating_hours_today')
                ->orderByDesc('total_hours')
                ->limit(6)
                ->get()
                ->pluck('total_hours', 'name')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels) || array_sum($values) <= 0) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data Available'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Operating Hours',
                    'data' => $values,
                    'backgroundColor' => '#f59e0b',
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
