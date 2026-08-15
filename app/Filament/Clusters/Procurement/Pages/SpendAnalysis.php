<?php

namespace App\Filament\Clusters\Procurement\Pages;

use App\Filament\Clusters\Procurement;
use App\Filament\Clusters\Procurement\Widgets;
use App\Models\PurchaseOrder;
use App\Services\ProcurementAnalyticsService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class SpendAnalysis extends Page
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'procurement.view';
    }

    protected static ?string $cluster = Procurement::class;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';

    protected static ?string $navigationLabel = 'Spend Analysis';

    protected static ?int $navigationSort = 18;

    protected static ?string $title = 'Spend Analysis';

    protected static string $view = 'filament.clusters.procurement.pages.spend-analysis';

    public function getHeader(): ?View
    {
        return view('filament.construction.partials.header-standalone', [
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 14.25v2.25m3-4.5v4.5m3-6.75v6.75m3-9v9M6 20.25h12A2.25 2.25 0 0020.25 18V6A2.25 2.25 0 0018 3.75H6A2.25 2.25 0 003.75 6v12A2.25 2.25 0 006 20.25z" />',
            'title' => 'Spend Analysis',
            'subtitle' => 'Spend by supplier, department, category and project — savings and budget variance.',
        ]);
    }

    public function getHeaderWidgets(): array
    {
        return [
            Widgets\SpendByDepartmentChart::class,
            Widgets\SpendBySupplierChart::class,
            Widgets\SpendByCategoryChart::class,
            Widgets\MonthlyProcurementChart::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 2;
    }

    public function getSpendByProject(): array
    {
        $companyId = Auth::user()?->company_id;

        return PurchaseOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('created_at', '>=', now()->subDays(90))
            ->whereNotNull('project_id')
            ->with('project')
            ->get()
            ->groupBy(fn (PurchaseOrder $o) => $o->project?->name ?? 'Unassigned')
            ->map(fn ($group) => round((float) $group->sum('total_amount'), 2))
            ->sortDesc()
            ->toArray();
    }

    public function getPurchaseSavings(): float
    {
        return app(ProcurementAnalyticsService::class)->purchaseSavingsAmount(Auth::user()?->company_id);
    }

    public function getBudgetVariance(): Collection
    {
        $companyId = Auth::user()?->company_id;
        $svc = app(\App\Services\BudgetValidationService::class);

        return \App\Models\ProcurementBudget::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->where('financial_year', (string) now()->year)
            ->get()
            ->map(function (\App\Models\ProcurementBudget $budget) use ($companyId, $svc) {
                $result = $svc->validate($companyId, [
                    'business_unit_id' => $budget->business_unit_id,
                    'department_id' => $budget->department_id,
                    'cost_centre_id' => $budget->cost_centre_id,
                    'project_id' => $budget->project_id,
                ], 0, $budget->financial_year);

                return [
                    'label' => $budget->category ?? $budget->budget_number,
                    'approved' => (float) $budget->approved_budget,
                    'committed' => $result['committed'],
                    'variance' => round((float) $budget->approved_budget - $result['committed'], 2),
                ];
            });
    }
}
