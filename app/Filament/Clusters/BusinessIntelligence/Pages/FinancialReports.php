<?php

namespace App\Filament\Clusters\BusinessIntelligence\Pages;

use App\Filament\Clusters\BusinessIntelligence;
use App\Services\Construction\BusinessIntelligenceService;
use App\Services\Construction\ReportExportService;
use App\Traits\HasModuleAccess;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class FinancialReports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'bi.view';
    }

    protected static ?string $cluster = BusinessIntelligence::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Financial Reports';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Financial Reports';

    protected static string $view = 'filament.clusters.business-intelligence.pages.hub-page';

    public function getPageIntro(): string
    {
        return 'Budget, cost, cash flow, profitability, revenue, invoices, payments, retention, forecast and earned value — already available in Project Finance, plus a new company-wide summary.';
    }

    public function getPageIcon(): string
    {
        return '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />';
    }

    public function getHubCards(): array
    {
        return [
            [
                'label' => 'Company-Wide Financial Summary',
                'description' => 'New: consolidated cost breakdown and revenue across every project.',
                'icon' => 'heroicon-o-document-arrow-down',
                'action' => 'exportCompanySummary',
            ],
            [
                'label' => 'Open Project Finance Reports',
                'description' => 'Budget, Cost, Cash Flow, Profitability, Revenue, Invoice, Payment, Retention, Variation, Earned Value and Executive Financial Dashboard.',
                'icon' => 'heroicon-o-arrow-top-right-on-square',
                'url' => \App\Filament\Clusters\ProjectFinance\Pages\Reports::getUrl(),
            ],
        ];
    }

    public function exportCompanySummary()
    {
        $companyId = Auth::user()?->company_id;
        $bi = app(BusinessIntelligenceService::class);
        $currency = session('currency', 'KES');

        $kpis = [
            ['label' => 'Total Contract Value', 'value' => $currency . ' ' . number_format($bi->totalContractValue($companyId), 0)],
            ['label' => 'Total Revenue', 'value' => $currency . ' ' . number_format($bi->totalRevenue($companyId), 0)],
            ['label' => 'Total Cost', 'value' => $currency . ' ' . number_format($bi->totalCost($companyId), 0)],
            ['label' => 'Profit', 'value' => $currency . ' ' . number_format($bi->profit($companyId), 0)],
            ['label' => 'Profit Margin', 'value' => $bi->profitMargin($companyId) . '%'],
        ];

        $breakdown = app(\App\Services\Construction\ProjectFinanceService::class)->companyCostBreakdown($companyId);
        $rows = collect($breakdown)->map(fn ($amount, $category) => [$category, $currency . ' ' . number_format($amount, 0)])->values()->all();

        return app(ReportExportService::class)->toPdf('Company-Wide Financial Summary', [
            ['title' => 'Cost Breakdown', 'columns' => ['Category', 'Amount'], 'rows' => $rows],
        ], $kpis);
    }
}
