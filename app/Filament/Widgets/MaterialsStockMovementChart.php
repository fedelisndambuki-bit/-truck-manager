<?php

namespace App\Filament\Widgets;

use App\Models\GoodsReceivedNote;
use App\Models\MaterialConsumption;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialsStockMovementChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Stock Movement — Received vs Issued';

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $received = GoodsReceivedNote::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', now()->subMonths(6))
                ->where('status', 'Approved')
                ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('sum(accepted_quantity) as total'))
                ->groupBy('month')->orderBy('month')->pluck('total', 'month');

            $issued = MaterialConsumption::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('date', '>=', now()->subMonths(6))
                ->select(DB::raw("strftime('%Y-%m', date) as month"), DB::raw('sum(quantity_used) as total'))
                ->groupBy('month')->orderBy('month')->pluck('total', 'month');
        } catch (\Exception $e) {
            $received = collect();
            $issued = collect();
        }

        $months = $received->keys()->merge($issued->keys())->unique()->sort()->values();

        if ($months->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['—', '—', '—'],
            ];
        }

        return [
            'datasets' => [
                ['label' => 'Received', 'data' => $months->map(fn ($m) => (float) ($received[$m] ?? 0))->toArray(), 'backgroundColor' => '#2f4f4f'],
                ['label' => 'Issued', 'data' => $months->map(fn ($m) => (float) ($issued[$m] ?? 0))->toArray(), 'backgroundColor' => '#c9704f'],
            ],
            'labels' => $months->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
