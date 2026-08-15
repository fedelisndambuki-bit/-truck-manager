<?php

namespace App\Filament\Widgets;

use App\Enums\TruckStatus;
use App\Models\Truck;
use Filament\Widgets\ChartWidget;

class TruckUtilizationWidget extends ChartWidget
{
    protected static ?string $heading = 'Truck Utilization';

    protected static ?string $maxHeight = '200px';

    protected static ?int $sort = 4;

    protected static ?string $pollingInterval = '30s';

    protected function getData(): array
    {
        $companyId = auth()->user()?->company_id;

        $query = Truck::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $available  = (clone $query)->where('status', TruckStatus::Available)->count();
        $inTransit  = (clone $query)->where('status', TruckStatus::InTransit)->count();
        $maintenance = (clone $query)->where('status', TruckStatus::UnderMaintenance)->count();

        return [
            'datasets' => [
                [
                    'data'            => [$available, $inTransit, $maintenance],
                    'backgroundColor' => ['#22c55e', '#3b82f6', '#ef4444'],
                ],
            ],
            'labels' => ['Available', 'In Transit', 'Under Maintenance'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
