<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Models\DocumentVersion;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RevisionTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Revision Trend';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = DocumentVersion::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('count(*) as total'))
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
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Revisions', 'data' => $values, 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#8b5cf6', 'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
