<?php

namespace App\Filament\Widgets\Quarry;

use App\Models\Maintenance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaintenanceTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Maintenance Trend (6 Months)';

    protected static ?int $sort = 16;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = Maintenance::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('equipment_id')
                ->where('maintenance_date', '>=', now()->subMonths(6))
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('maintenance_date', 'month')), DB::raw('count(*) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('intval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($values)) {
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
                    'backgroundColor' => '#f97316',
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
