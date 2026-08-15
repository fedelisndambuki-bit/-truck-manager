<?php

namespace App\Filament\Clusters\ProjectFinance\Widgets;

use App\Models\Expense;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProfitTrendChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Profit Trend';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $revenue = Payment::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('project_id')->where('status', 'Paid')
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('created_at', 'month')), DB::raw('sum(amount) as total'))
                ->groupBy('month')->orderBy('month')->pluck('total', 'month')->toArray();

            $cost = Expense::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->whereNotNull('project_id')
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('created_at', 'month')), DB::raw('sum(amount) as total'))
                ->groupBy('month')->orderBy('month')->pluck('total', 'month')->toArray();

            $months = collect(array_keys($revenue))->merge(array_keys($cost))->unique()->sort()->values();
            $profit = $months->map(fn ($m) => (float) ($revenue[$m] ?? 0) - (float) ($cost[$m] ?? 0))->toArray();
            $labels = $months->toArray();
        } catch (\Exception $e) {
            $labels = [];
            $profit = [];
        }

        if (empty($labels)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Profit', 'data' => $profit, 'fill' => 'start', 'tension' => 0.4,
                'borderColor' => '#8b5cf6', 'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
            ]],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
