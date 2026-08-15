<?php

namespace App\Filament\Widgets;

use App\Models\MaterialDelivery;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialConsumptionChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Material Consumption';

    protected static ?int $sort = 17;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = MaterialDelivery::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('status', 'Delivered')
                ->where('delivered_at', '>=', now()->subDays(30))
                ->join('materials', 'materials.id', '=', 'material_deliveries.material_id')
                ->select('materials.name as material_name', DB::raw('sum(material_deliveries.quantity) as total'))
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
                    'label' => 'Quantity Delivered (30 days)',
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
