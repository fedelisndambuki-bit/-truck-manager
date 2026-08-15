<?php

namespace App\Filament\Clusters\ProductionManagement\Widgets;

use App\Models\ProductionTarget;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class TargetVsActualChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Target vs Actual';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $targets = ProductionTarget::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->with('extractionSite')
                ->latest('period_start')
                ->limit(6)
                ->get();

            $labels = $targets->map(fn (ProductionTarget $t) => ($t->extractionSite?->name ?? 'Site') . ' — ' . $t->period_type)->toArray();
            $targetValues = $targets->map(fn (ProductionTarget $t) => (float) $t->target_quantity)->toArray();
            $actualValues = $targets->map(fn (ProductionTarget $t) => $t->actualQuantity())->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $targetValues = [];
            $actualValues = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Target', 'data' => $targetValues, 'backgroundColor' => '#9ca3af'],
                ['label' => 'Actual', 'data' => $actualValues, 'backgroundColor' => '#10b981'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
