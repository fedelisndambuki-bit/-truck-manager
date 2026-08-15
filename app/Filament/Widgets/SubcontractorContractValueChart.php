<?php

namespace App\Filament\Widgets;

use App\Models\Contract;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SubcontractorContractValueChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Contract Value by Subcontractor';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $contracts = Contract::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->with('subcontractor')
            ->orderByDesc('contract_value')
            ->limit(8)
            ->get();

        if ($contracts->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Contracts Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Contract Value (' . session('currency', 'KES') . ')',
                'data' => $contracts->map(fn (Contract $c) => (float) $c->contract_value)->toArray(),
                'backgroundColor' => '#2f4f4f',
            ]],
            'labels' => $contracts->map(fn (Contract $c) => $c->subcontractor?->company_name ?? $c->contract_number)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
