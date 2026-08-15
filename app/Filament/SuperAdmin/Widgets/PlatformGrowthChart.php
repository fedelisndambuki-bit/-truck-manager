<?php

namespace App\Filament\SuperAdmin\Widgets;

use App\Models\Company;
use Filament\Widgets\ChartWidget;

class PlatformGrowthChart extends ChartWidget
{
    protected static ?string $heading = 'Company Growth (Last 6 Months)';

    protected function getData(): array
    {
        $labels = [];
        $series = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->format('M Y');
            $series[] = Company::whereYear('created_at', $month->year)
                ->whereMonth('created_at', $month->month)
                ->count();
        }

        return [
            'datasets' => [
                [
                    'label' => 'New Companies',
                    'data' => $series,
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
