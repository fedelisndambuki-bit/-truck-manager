<?php

namespace App\Filament\Clusters\CrmSales\Widgets;

use App\Models\Opportunity;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SalesPipelineChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Sales Pipeline by Stage';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;
        $stages = ['Qualification', 'Proposal', 'Negotiation', 'Verbal Commitment', 'Won', 'Lost'];

        $counts = collect($stages)->map(fn ($stage) => Opportunity::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('stage', $stage)
            ->count());

        return [
            'datasets' => [
                [
                    'label' => 'Opportunities',
                    'data' => $counts->values()->all(),
                    'backgroundColor' => ['#9ca3af', '#3b82f6', '#f59e0b', '#f59e0b', '#10b981', '#ef4444'],
                ],
            ],
            'labels' => $stages,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
