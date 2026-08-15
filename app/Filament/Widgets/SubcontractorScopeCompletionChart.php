<?php

namespace App\Filament\Widgets;

use App\Models\ScopeOfWork;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SubcontractorScopeCompletionChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Scope Completion';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $scopes = ScopeOfWork::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('contract.subcontractor')
            ->limit(8)
            ->get();

        if ($scopes->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Scope Defined Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => '% Complete',
                'data' => $scopes->map(fn (ScopeOfWork $s) => (float) $s->percent_complete)->toArray(),
                'backgroundColor' => '#0891b2',
            ]],
            'labels' => $scopes->map(fn (ScopeOfWork $s) => $s->contract?->subcontractor?->company_name ?? $s->contract?->contract_number ?? ('Scope #' . $s->id))->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
