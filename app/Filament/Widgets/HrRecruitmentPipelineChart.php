<?php

namespace App\Filament\Widgets;

use App\Models\JobApplication;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrRecruitmentPipelineChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Recruitment Pipeline';

    protected static ?int $sort = 9;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $stages = ['Applied', 'Shortlisted', 'Interview', 'Offer', 'Hired', 'Rejected'];

        $counts = JobApplication::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        if ($counts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Applications'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Applications',
                'data' => collect($stages)->map(fn ($stage) => (int) ($counts[$stage] ?? 0))->toArray(),
                'backgroundColor' => '#2f4f4f',
            ]],
            'labels' => $stages,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
