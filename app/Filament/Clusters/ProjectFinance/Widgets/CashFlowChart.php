<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CashFlowChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Cash Flow';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $cashIn = Payment::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('project_id')->where('status', 'Paid')
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('sum(amount) as total'))
                ->groupBy('month')->orderBy('month')->pluck('total', 'month')->toArray();

            $cashOut = Expense::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('project_id')
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('sum(amount) as total'))
                ->groupBy('month')->orderBy('month')->pluck('total', 'month')->toArray();

            $months = collect(array_keys($cashIn))->merge(array_keys($cashOut))->unique()->sort()->values();
            $inValues = $months->map(fn ($m) => (float) ($cashIn[$m] ?? 0))->toArray();
            $outValues = $months->map(fn ($m) => (float) ($cashOut[$m] ?? 0))->toArray();
            $labels = $months->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $inValues = [];
            $outValues = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Cash In', 'data' => $inValues, 'backgroundColor' => '#10b981'],
                ['label' => 'Cash Out', 'data' => $outValues, 'backgroundColor' => '#ef4444'],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
