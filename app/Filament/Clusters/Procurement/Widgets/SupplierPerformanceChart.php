<?php

namespace App\Filament\Clusters\Procurement\Widgets;

use App\Models\Supplier;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SupplierPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Top Suppliers by Performance Rating';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $raw = Supplier::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('performance_rating')
                ->orderByDesc('performance_rating')
                ->limit(8)
                ->pluck('performance_rating', 'company_name')
                ->toArray();
        } catch (\Exception $e) {
            $raw = [];
        }

        if (empty($raw)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Rating', 'data' => array_map('floatval', array_values($raw)), 'backgroundColor' => '#f59e0b']],
            'labels' => array_keys($raw),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
