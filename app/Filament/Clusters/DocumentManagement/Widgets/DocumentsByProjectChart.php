<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Models\EdmsDocument;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class DocumentsByProjectChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Documents by Project';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rows = EdmsDocument::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('project_id')
                ->with('project:id,name')
                ->get()
                ->groupBy(fn (EdmsDocument $d) => $d->project?->name ?? 'Unassigned')
                ->map->count()
                ->sortDesc()
                ->take(8);
        } catch (\Exception $e) {
            $rows = collect();
        }

        if ($rows->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['No documents yet'],
            ];
        }

        return [
            'datasets' => [['label' => 'Documents', 'data' => $rows->values()->all(), 'backgroundColor' => '#2563eb']],
            'labels' => $rows->keys()->map(fn ($k) => str($k)->limit(14)->toString())->all(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
