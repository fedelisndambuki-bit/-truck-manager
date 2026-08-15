<?php

namespace App\Filament\Widgets;

use App\Models\MaterialDelivery;
use App\Models\Project;
use Filament\Widgets\ChartWidget;

class ProjectMaterialConsumptionChart extends ChartWidget
{
    public ?Project $record = null;

    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '220px';

    protected static ?string $heading = 'Material Consumption';

    public function mount(?Project $record = null): void
    {
        $this->record = $record;
    }

    protected function getData(): array
    {
        if (! $this->record) {
            return ['datasets' => [], 'labels' => []];
        }

        try {
            $siteIds = $this->record->sites()->pluck('id');

            $rawData = MaterialDelivery::query()
                ->whereIn('site_id', $siteIds)
                ->where('status', 'Delivered')
                ->join('materials', 'materials.id', '=', 'material_deliveries.material_id')
                ->selectRaw('materials.name as material_name, sum(material_deliveries.quantity) as total')
                ->groupBy('materials.name')
                ->orderByDesc('total')
                ->limit(6)
                ->pluck('total', 'material_name')
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
                    'label' => 'Quantity Delivered',
                    'data' => $values,
                    'backgroundColor' => '#0ea5e9',
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
