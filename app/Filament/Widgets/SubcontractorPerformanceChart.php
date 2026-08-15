<?php

namespace App\Filament\Widgets;

use App\Models\Subcontractor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class SubcontractorPerformanceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Subcontractor Performance';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $subcontractors = collect();

        if (Schema::hasTable('subcontractor_performance_reviews')) {
            $subcontractors = Subcontractor::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->get()
                ->filter(fn (Subcontractor $s) => $s->average_performance_rating !== null)
                ->sortByDesc('average_performance_rating')
                ->take(8);
        }

        if ($subcontractors->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Ratings Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Avg. Rating (out of 5)',
                'data' => $subcontractors->map(fn (Subcontractor $s) => round((float) $s->average_performance_rating, 2))->values()->toArray(),
                'backgroundColor' => '#4d7c7c',
            ]],
            'labels' => $subcontractors->pluck('company_name')->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
