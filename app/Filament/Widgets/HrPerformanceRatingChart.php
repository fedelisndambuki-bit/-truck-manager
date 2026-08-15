<?php

namespace App\Filament\Widgets;

use App\Models\PerformanceReview;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrPerformanceRatingChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Performance Rating Distribution';

    protected static ?int $sort = 11;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $reviews = PerformanceReview::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('rating')
            ->get();

        if ($reviews->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Reviews'],
            ];
        }

        $buckets = ['0-1' => 0, '1-2' => 0, '2-3' => 0, '3-4' => 0, '4-5' => 0];
        foreach ($reviews as $review) {
            $rating = (float) $review->rating;
            $bucket = match (true) {
                $rating < 1 => '0-1',
                $rating < 2 => '1-2',
                $rating < 3 => '2-3',
                $rating < 4 => '3-4',
                default => '4-5',
            };
            $buckets[$bucket]++;
        }

        return [
            'datasets' => [[
                'label' => 'Reviews',
                'data' => array_values($buckets),
                'backgroundColor' => '#4d7c7c',
            ]],
            'labels' => array_keys($buckets),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
