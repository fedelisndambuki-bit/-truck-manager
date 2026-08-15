<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\SafetyTrainingResource;
use App\Models\TrainingRecord;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyTrainingComplianceChart extends ChartWidget
{
    protected static ?string $heading = 'Training Compliance';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 8;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $counts = TrainingRecord::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('training_type', SafetyTrainingResource::SAFETY_TYPES)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($counts->isEmpty()) {
            return ['datasets' => [['label' => 'Training Records', 'data' => [0], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['No Data']];
        }

        $colorMap = ['Completed' => '#22c55e', 'Scheduled' => '#3b82f6', 'Expired' => '#dc2626'];

        return [
            'datasets' => [[
                'label' => 'Training Records',
                'data' => $counts->values()->toArray(),
                'backgroundColor' => collect($counts->keys())->map(fn ($k) => $colorMap[$k] ?? '#9ca3af')->toArray(),
            ]],
            'labels' => $counts->keys()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
