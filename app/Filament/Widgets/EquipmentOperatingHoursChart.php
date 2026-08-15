<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\EquipmentAllocation;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EquipmentOperatingHoursChart extends ChartWidget
{
    public ?Equipment $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Operating Hours';

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
                ->where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('date(created_at) as day'), DB::raw('sum(operating_hours_today) as total'))
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Week 1', 'Week 2', 'Week 3'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Operating Hours',
                    'data' => $values,
                    'fill' => 'start',
                    'tension' => 0.4,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
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
