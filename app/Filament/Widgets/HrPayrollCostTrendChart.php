<?php

namespace App\Filament\Widgets;

use App\Models\PayrollPeriod;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class HrPayrollCostTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Payroll Cost Trend';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $periods = PayrollPeriod::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->orderBy('year')->orderBy('month')
            ->limit(6)
            ->get();

        if ($periods->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['—', '—', '—'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Net Payroll Cost',
                'data' => $periods->pluck('total_net')->map(fn ($v) => (float) $v)->toArray(),
                'borderColor' => '#166534',
                'backgroundColor' => 'rgba(22,101,52,0.1)',
            ]],
            'labels' => $periods->map(fn ($p) => \App\Filament\Resources\PayrollPeriodResource::getMonthOptions()[$p->month] . ' ' . $p->year)->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
