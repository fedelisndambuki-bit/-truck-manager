<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use Filament\Widgets\ChartWidget;

class EquipmentUtilizationRateChart extends ChartWidget
{
    public ?Equipment $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Utilization % (by Project)';

    public function mount(?Equipment $record = null): void
    {
        $this->record = $record;
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        try {
            $rawData = EquipmentAllocation::query()
                ->where('equipment_id', $this->record->id)
                ->join('projects', 'projects.id', '=', 'equipment_allocations.project_id')
                ->selectRaw('projects.name as project_name, sum(equipment_allocations.operating_hours_today) as total')
                ->groupBy('projects.name')
                ->orderByDesc('total')
                ->limit(6)
                ->pluck('total', 'project_name')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'No Data', 'data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data Available'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Hours by Project',
                    'data' => $values,
                    'backgroundColor' => ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#0ea5e9', '#ef4444'],
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
