<?php

namespace App\Filament\Clusters\CrmSales\Widgets;

use App\Models\Lead;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class LeadSourceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Leads by Source';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $bySource = Lead::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('COALESCE(source, ?) as source, COUNT(*) as total', ['Unknown'])
            ->groupBy('source')
            ->pluck('total', 'source');

        if ($bySource->isEmpty()) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['Waiting for Data'],
            ];
        }

        return [
            'datasets' => [
                [
                    'data' => $bySource->values()->all(),
                    'backgroundColor' => ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b', '#ec4899', '#0891b2'],
                ],
            ],
            'labels' => $bySource->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
