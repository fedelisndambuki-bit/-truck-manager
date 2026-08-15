<?php

namespace App\Filament\Widgets;

use App\Models\TrainingRecord;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrTrainingComplianceChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Training by Type';

    protected static ?int $sort = 7;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = TrainingRecord::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('training_type, count(*) as total')
            ->groupBy('training_type')
            ->pluck('total', 'training_type');

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Records'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Records',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => '#8fb8b8',
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
