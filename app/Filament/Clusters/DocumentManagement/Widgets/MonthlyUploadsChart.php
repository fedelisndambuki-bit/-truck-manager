<?php

namespace App\Filament\Clusters\DocumentManagement\Widgets;

use App\Models\EdmsDocument;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class MonthlyUploadsChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $maxHeight = '260px';

    protected static ?string $heading = 'Monthly Uploads';

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        try {
            $rawData = EdmsDocument::query()
                ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
                ->where('created_at', '>=', now()->subMonths(6))
                ->select(DB::raw(\App\Support\SqlDateFormat::monthly('created_at', 'month')), DB::raw('count(*) as total'))
                ->groupBy('month')
                ->orderBy('month')
                ->pluck('total', 'month')
                ->toArray();

            $labels = array_keys($rawData);
            $values = array_map('intval', array_values($rawData));
        } catch (\Exception $e) {
            $labels = [];
            $values = [];
        }

        if (empty($values)) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0, 0, 0, 0, 0, 0], 'backgroundColor' => '#9ca3af']],
                'labels' => ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            ];
        }

        return [
            'datasets' => [['label' => 'Uploads', 'data' => $values, 'backgroundColor' => '#2563eb']],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
