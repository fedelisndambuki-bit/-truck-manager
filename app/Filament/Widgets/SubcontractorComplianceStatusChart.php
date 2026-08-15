<?php

namespace App\Filament\Widgets;

use App\Models\Subcontractor;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SubcontractorComplianceStatusChart extends ChartWidget
{
    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = 'Compliance Status';

    protected static ?int $sort = 6;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $subcontractors = Subcontractor::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get();

        $compliant = $subcontractors->filter(fn (Subcontractor $s) => $s->compliance_status === 'Compliant')->count();
        $nonCompliant = $subcontractors->count() - $compliant;

        if ($subcontractors->isEmpty()) {
            return [
                'datasets' => [['label' => 'Waiting for Data', 'data' => [0], 'backgroundColor' => ['#9ca3af']]],
                'labels' => ['No Subcontractors Yet'],
            ];
        }

        return [
            'datasets' => [[
                'label' => 'Subcontractors',
                'data' => [$compliant, $nonCompliant],
                'backgroundColor' => ['#16a34a', '#dc2626'],
            ]],
            'labels' => ['Compliant', 'Non-Compliant'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
