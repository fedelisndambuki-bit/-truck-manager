<?php

namespace App\Filament\Widgets;

use App\Models\PurchaseOrder;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MaterialsMonthlyProcurementChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Monthly Procurement Spend';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = PurchaseOrder::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw("strftime('%Y-%m', created_at) as month"), DB::raw('sum(total_amount) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();
        } catch (\Exception $e) {
            $rawData = [];
        }

        if (empty($rawData)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'borderColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Procurement Spend',
                'data' => array_map('floatval', array_values($rawData)),
                'borderColor' => '#166534',
                'backgroundColor' => 'rgba(22,101,52,0.1)',
            ]],
            'labels' => array_keys($rawData),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
