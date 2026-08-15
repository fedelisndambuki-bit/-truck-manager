<?php

namespace App\Filament\Clusters\ProjectFinance\Pages;

use App\Filament\Clusters\ProjectFinance;
use App\Models\ChangeOrder;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\ProjectBudget;
use App\Models\RetentionEntry;
use App\Services\Construction\ProjectFinanceService;
use App\Traits\HasModuleAccess;
use Barryvdh\DomPDF\Facade\Pdf;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

class Reports extends Page
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'project_finance.export';
    }

    protected static ?string $cluster = ProjectFinance::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static ?string $navigationLabel = 'Reports';

    protected static ?int $navigationSort = 18;

    protected static ?string $title = 'Reports';

    protected static string $view = 'filament.clusters.project-finance.pages.reports';

    protected function companyId(): ?int
    {
        return Auth::user()?->company_id;
    }

    public function getReportDefinitions(): array
    {
        return [
            ['key' => 'budget', 'label' => 'Budget Report', 'icon' => 'heroicon-o-calculator'],
            ['key' => 'cost', 'label' => 'Cost Report', 'icon' => 'heroicon-o-adjustments-horizontal'],
            ['key' => 'cash_flow', 'label' => 'Cash Flow Report', 'icon' => 'heroicon-o-arrow-trending-up'],
            ['key' => 'profitability', 'label' => 'Profitability Report', 'icon' => 'heroicon-o-scale'],
            ['key' => 'revenue', 'label' => 'Revenue Report', 'icon' => 'heroicon-o-chart-bar-square'],
            ['key' => 'invoice', 'label' => 'Invoice Report', 'icon' => 'heroicon-o-receipt-percent'],
            ['key' => 'payment', 'label' => 'Payment Report', 'icon' => 'heroicon-o-banknotes'],
            ['key' => 'retention', 'label' => 'Retention Report', 'icon' => 'heroicon-o-lock-closed'],
            ['key' => 'variation', 'label' => 'Variation Report', 'icon' => 'heroicon-o-arrows-right-left'],
            ['key' => 'earned_value', 'label' => 'Earned Value Report', 'icon' => 'heroicon-o-presentation-chart-line'],
            ['key' => 'executive', 'label' => 'Executive Financial Dashboard', 'icon' => 'heroicon-o-building-office-2'],
        ];
    }

    protected function projects()
    {
        return Project::query()->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))->get();
    }

    protected function download(string $title, array $kpis, array $sections, string $filename)
    {
        $pdf = Pdf::loadView('reports.project-finance-report', [
            'title' => $title,
            'date' => now()->format('d M Y'),
            'kpis' => $kpis,
            'sections' => $sections,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->stream();
        }, $filename . '-' . now()->format('Y-m-d') . '.pdf');
    }

    protected function currency(): string
    {
        return session('currency', 'KES');
    }

    public function generateBudgetReport()
    {
        $currency = $this->currency();
        $rows = ProjectBudget::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('project')->get()
            ->map(fn (ProjectBudget $b) => [
                $b->budget_number, $b->project?->name, $b->category,
                $currency . ' ' . number_format((float) $b->approved_budget, 0),
                $currency . ' ' . number_format((float) $b->actual_cost, 0),
                $currency . ' ' . number_format($b->variance, 0),
                $b->status,
            ])->all();

        return $this->download('Budget Report', [], [
            ['title' => 'Project Budgets', 'columns' => ['Budget #', 'Project', 'Category', 'Approved', 'Actual', 'Variance', 'Status'], 'rows' => $rows],
        ], 'budget-report');
    }

    public function generateCostReport()
    {
        $service = app(ProjectFinanceService::class);
        $currency = $this->currency();
        $rows = $this->projects()->map(function (Project $p) use ($service, $currency) {
            $b = $service->costBreakdown($p);

            return [
                $p->name,
                $currency . ' ' . number_format($b['Labour'], 0),
                $currency . ' ' . number_format($b['Materials'], 0),
                $currency . ' ' . number_format($b['Equipment'], 0),
                $currency . ' ' . number_format($b['Subcontractors'], 0),
                $currency . ' ' . number_format($b['Procurement'], 0),
                $currency . ' ' . number_format($b['Delay Impact'], 0),
                $currency . ' ' . number_format($b['Quality Rework'], 0),
            ];
        })->all();

        return $this->download('Cost Report', [], [
            ['title' => 'Cost by Project', 'columns' => ['Project', 'Labour', 'Materials', 'Equipment', 'Subcontractors', 'Procurement', 'Delay Impact', 'Quality Rework'], 'rows' => $rows],
        ], 'cost-report');
    }

    public function generateCashFlowReport()
    {
        $service = app(ProjectFinanceService::class);
        $currency = $this->currency();
        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name,
            $currency . ' ' . number_format($service->cashIn($p), 0),
            $currency . ' ' . number_format($service->cashOut($p), 0),
            $currency . ' ' . number_format($service->projectedCashFlow($p), 0),
        ])->all();

        return $this->download('Cash Flow Report', [], [
            ['title' => 'Project Cash Flow', 'columns' => ['Project', 'Cash In', 'Cash Out', 'Projected'], 'rows' => $rows],
        ], 'cash-flow-report');
    }

    public function generateProfitabilityReport()
    {
        $service = app(ProjectFinanceService::class);
        $currency = $this->currency();
        $rows = $this->projects()->map(function (Project $p) use ($service, $currency) {
            $revenue = $service->totalRevenue($p);
            $cost = $service->totalActualCost($p);

            return [$p->name, $currency . ' ' . number_format($revenue, 0), $currency . ' ' . number_format($cost, 0), $currency . ' ' . number_format($revenue - $cost, 0), $revenue > 0 ? round((($revenue - $cost) / $revenue) * 100, 1) . '%' : '—'];
        })->all();

        return $this->download('Profitability Report', [], [
            ['title' => 'Project Profitability', 'columns' => ['Project', 'Revenue', 'Cost', 'Profit', 'Margin'], 'rows' => $rows],
        ], 'profitability-report');
    }

    public function generateRevenueReport()
    {
        $service = app(ProjectFinanceService::class);
        $currency = $this->currency();
        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name,
            $currency . ' ' . number_format((float) ($p->contract_value ?: 0), 0),
            $currency . ' ' . number_format($service->totalRevenue($p), 0),
            $currency . ' ' . number_format($service->outstandingClientAmount($p), 0),
        ])->all();

        return $this->download('Revenue Report', [], [
            ['title' => 'Revenue by Project', 'columns' => ['Project', 'Contract Value', 'Revenue Received', 'Outstanding'], 'rows' => $rows],
        ], 'revenue-report');
    }

    public function generateInvoiceReport()
    {
        $currency = $this->currency();
        $rows = Invoice::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('project_id')->with(['project', 'client'])->get()
            ->map(fn (Invoice $i) => [$i->invoice_number, $i->project?->name, $i->client?->company_name, $currency . ' ' . number_format((float) $i->total_amount, 0), $i->status])
            ->all();

        return $this->download('Invoice Report', [], [
            ['title' => 'Client Invoices', 'columns' => ['Invoice #', 'Project', 'Client', 'Amount', 'Status'], 'rows' => $rows],
        ], 'invoice-report');
    }

    public function generatePaymentReport()
    {
        $currency = $this->currency();
        $rows = \App\Models\Payment::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->whereNotNull('project_id')->with('project')->get()
            ->map(fn ($p) => [$p->project?->name, $currency . ' ' . number_format((float) $p->amount, 0), $p->payment_method, $p->status, optional($p->payment_date)->format('d M Y')])
            ->all();

        return $this->download('Payment Report', [], [
            ['title' => 'Payments Received', 'columns' => ['Project', 'Amount', 'Method', 'Status', 'Date'], 'rows' => $rows],
        ], 'payment-report');
    }

    public function generateRetentionReport()
    {
        $currency = $this->currency();
        $rows = RetentionEntry::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->with('project')->get()
            ->map(fn (RetentionEntry $r) => [$r->project?->name, $r->source_type, $currency . ' ' . number_format((float) $r->held_amount, 0), $currency . ' ' . number_format((float) $r->released_amount, 0), $currency . ' ' . number_format($r->outstanding, 0), $r->release_status])
            ->all();

        return $this->download('Retention Report', [], [
            ['title' => 'Retention Held & Released', 'columns' => ['Project', 'Source', 'Held', 'Released', 'Outstanding', 'Status'], 'rows' => $rows],
        ], 'retention-report');
    }

    public function generateVariationReport()
    {
        $currency = $this->currency();
        $rows = ChangeOrder::query()
            ->when($this->companyId(), fn ($q) => $q->where('company_id', $this->companyId()))
            ->where('type', 'client_variation')->with('project')->get()
            ->map(fn (ChangeOrder $c) => [$c->co_number, $c->project?->name, $c->title, $currency . ' ' . number_format((float) $c->cost_impact, 0), $c->time_impact_days . ' days', $c->status])
            ->all();

        return $this->download('Variation Report', [], [
            ['title' => 'Client Variations', 'columns' => ['Variation #', 'Project', 'Title', 'Cost Impact', 'Time Impact', 'Status'], 'rows' => $rows],
        ], 'variation-report');
    }

    public function generateEarnedValueReport()
    {
        $service = app(ProjectFinanceService::class);
        $currency = $this->currency();
        $rows = $this->projects()->map(fn (Project $p) => [
            $p->name,
            $currency . ' ' . number_format($service->earnedValue($p), 0),
            $currency . ' ' . number_format($service->plannedValue($p), 0),
            number_format($service->costPerformanceIndex($p), 2),
            number_format($service->schedulePerformanceIndex($p), 2),
            $currency . ' ' . number_format($service->costVariance($p), 0),
            $currency . ' ' . number_format($service->scheduleVariance($p), 0),
        ])->all();

        return $this->download('Earned Value Report', [], [
            ['title' => 'Earned Value Analysis', 'columns' => ['Project', 'EV', 'PV', 'CPI', 'SPI', 'CV', 'SV'], 'rows' => $rows],
        ], 'earned-value-report');
    }

    public function generateExecutiveReport()
    {
        $service = app(ProjectFinanceService::class);
        $currency = $this->currency();
        $projects = $this->projects();

        $totalRevenue = $projects->sum(fn (Project $p) => $service->totalRevenue($p));
        $totalCost = $projects->sum(fn (Project $p) => $service->totalActualCost($p));

        $kpis = [
            ['label' => 'Total Contract Value', 'value' => $currency . ' ' . number_format($projects->sum(fn (Project $p) => (float) ($p->contract_value ?: 0)), 0)],
            ['label' => 'Total Revenue', 'value' => $currency . ' ' . number_format($totalRevenue, 0)],
            ['label' => 'Total Cost', 'value' => $currency . ' ' . number_format($totalCost, 0)],
            ['label' => 'Gross Profit', 'value' => $currency . ' ' . number_format($totalRevenue - $totalCost, 0)],
        ];

        $rows = $projects->map(function (Project $p) use ($service, $currency) {
            $revenue = $service->totalRevenue($p);
            $cost = $service->totalActualCost($p);

            return [$p->name, $p->status, $currency . ' ' . number_format($revenue, 0), $currency . ' ' . number_format($cost, 0), $currency . ' ' . number_format($revenue - $cost, 0)];
        })->all();

        return $this->download('Executive Financial Dashboard', $kpis, [
            ['title' => 'Project Summary', 'columns' => ['Project', 'Status', 'Revenue', 'Cost', 'Profit'], 'rows' => $rows],
        ], 'executive-financial-report');
    }
}
