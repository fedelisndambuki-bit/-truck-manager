<?php

namespace App\Filament\Widgets;

use App\Models\Equipment;
use App\Models\Maintenance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class EquipmentDowntimeChart extends ChartWidget
{
    public ?Equipment $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Downtime (Maintenance Events)';

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
            $rawData = Maintenance::query()
                ->where('equipment_id', $this->record->id)
                ->where('maintenance_date', '>=', now()->subMonths(6))
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('maintenance_date', 'month')), DB::raw('count(*) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('floatval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [
                [
                    'label' => 'Maintenance Events',
                    'data' => $values,
                    'backgroundColor' => '#ef4444',
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
