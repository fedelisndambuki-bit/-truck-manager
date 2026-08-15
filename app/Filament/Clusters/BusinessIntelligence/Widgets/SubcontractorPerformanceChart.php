<?php

namespace App\Filament\Clusters\BusinessIntelligence\Widgets;

use App\Models\Subcontractor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SubcontractorPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Subcontractor Performance';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $subcontractors = Subcontractor::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->get()
                ->map(fn (Subcontractor $s) => ['name' => $s->company_name, 'rating' => $s->average_performance_rating])
                ->filter(fn ($row) => $row['rating'] !== null)
                ->sortByDesc('rating')
                ->take(8);

            $labels = $subcontractors->pluck('name')->toArray();
            $values = $subcontractors->pluck('rating')->map(fn ($v) => round((float) $v, 1))->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'No Performance Reviews Yet', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Data'],
            ];
        }

        return [
            'datasets' => [['label' => 'Avg Rating (out of 5)', 'data' => $values, 'backgroundColor' => '#6366f1']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
