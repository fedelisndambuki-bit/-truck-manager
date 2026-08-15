<?php

namespace App\Filament\Widgets;

use App\Models\PpeIssuance;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class SafetyPpeComplianceChart extends ChartWidget
{
    protected static ?string $heading = 'PPE Compliance';

    protected int|string|array $columnSpan = 1;

    protected static ?int $sort = 5;

    protected function getData(): array
    {
        $companyId = Auth::user()?->company_id;

        $ppe = PpeIssuance::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', '!=', 'Returned')
            ->get();

        $compliant = $ppe->filter(fn ($p) => ! $p->replacement_due_date || $p->replacement_due_date->diffInDays(now(), false) < -14)->count();
        $dueSoon = $ppe->filter(fn ($p) => $p->replacement_due_date && $p->replacement_due_date->isFuture() && $p->replacement_due_date->diffInDays(now()) <= 14)->count();
        $overdue = $ppe->filter(fn ($p) => $p->replacement_due_date && $p->replacement_due_date->isPast())->count();

        if ($ppe->isEmpty()) {
            return ['datasets' => [['label' => 'PPE', 'data' => [0], 'backgroundColor' => ['#9ca3af']]], 'labels' => ['No Data']];
        }

        return [
            'datasets' => [[
                'label' => 'PPE Issuances',
                'data' => [$compliant, $dueSoon, $overdue],
                'backgroundColor' => ['#22c55e', '#eab308', '#dc2626'],
            ]],
            'labels' => ['Compliant', 'Due Soon', 'Overdue'],
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
