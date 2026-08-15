<?php

namespace App\Filament\Widgets;

use App\Models\EquipmentAllocation;
use App\Models\Site;
use Filament\Widgets\ChartWidget;

class SiteEquipmentUtilizationChart extends ChartWidget
{
    public ?Site $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Equipment Utilization';

    public function mount(?Site $record = null): void
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
                ->where('site_id', $this->record->id)
                ->join('equipment', 'equipment.id', '=', 'equipment_allocations.equipment_id')
                ->selectRaw('equipment.name as equipment_name, sum(equipment_allocations.operating_hours_today) as total')
                ->groupBy('equipment.name')
                ->orderByDesc('total')
                ->limit(6)
                ->pluck('total', 'equipment_name')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
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
