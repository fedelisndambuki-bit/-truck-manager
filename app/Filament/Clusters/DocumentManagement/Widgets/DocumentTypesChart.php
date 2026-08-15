<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Models\EdmsDocument;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DocumentTypesChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Document Types';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $counts = EdmsDocument::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->selectRaw('category, count(*) as total')
                ->groupBy('category')
                ->orderByDesc('total')
                ->limit(8)
                ->pluck('total', 'category');
        } catch (\Exception $e) {
            $counts = collect();
        }

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['data' => [1], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['Waiting for Data'],
            ];
        }

        return [
            'datasets' => [[
                'data' => $counts->values()->all(),
                'backgroundColor' => ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ef4444', '#64748b', '#ec4899', '#0891b2'],
            ]],
            'labels' => $counts->keys()->all(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
