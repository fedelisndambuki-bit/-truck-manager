<?php

namespace App\Filament\SuperAdmin\Pages;

use App\Models\Company;
use App\Models\Subscription;
use App\Models\SubscriptionPayment;
use App\Models\Truck;
use App\Models\AiUsageLog;
use App\Models\PlatformFinancialSetting;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use OpenSpout\Writer\XLSX\Writer;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Cell;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialIntelligenceCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-bar';
    protected static ?string $navigationLabel = 'Financial Intelligence Center';
    protected static ?string $navigationGroup = 'Financials';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.super-admin.pages.financial-intelligence-center';

    public function downloadPdf()
    {
        $data = $this->getViewData();
        $pdf = Pdf::loadView('filament.super-admin.pages.financial-report-pdf', $data);
        return response()->streamDownload(fn () => print($pdf->output()), 'Financial_Intelligence_Report_' . now()->format('Y-m-d') . '.pdf');
    }

    public function exportExcel(): StreamedResponse
    {
        $data = $this->getViewData();

        return response()->streamDownload(function () use ($data) {
            $writer = new Writer();
            $writer->openToBrowser('Financial_Data_Export_' . now()->format('Y-m-d') . '.xlsx');

            $writer->addRow(Row::fromValues(['Financial Summary Report', now()->format('Y-m-d')]));
            $writer->addRow(Row::fromValues(['Metric', 'Value']));
            
            $writer->addRow(Row::fromValues(['Total MRR', 'KES ' . number_format($data['mrr'], 2)]));
            $writer->addRow(Row::fromValues(['Annual Run Rate (ARR)', 'KES ' . number_format($data['arr'], 2)]));
            $writer->addRow(Row::fromValues(['Gross Margin', $data['grossMargin'] . '%']));
            $writer->addRow(Row::fromValues(['Net Profit (Monthly)', 'KES ' . number_format($data['netProfit'], 2)]));
            $writer->addRow(Row::fromValues(['Active Vehicles', $data['activeVehicles']]));
            $writer->addRow(Row::fromValues(['Churn Rate', $data['churnRate'] . '%']));
            $writer->addRow(Row::fromValues(['LTV', 'KES ' . number_format($data['ltv'], 2)]));
            $writer->addRow(Row::fromValues(['CAC', 'KES ' . number_format($data['cac'], 2)]));
            $writer->addRow(Row::fromValues(['Health Score', $data['healthScore'] . '/100']));

            $writer->close();
        }, 'Financial_Data_Export_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function getViewData(): array
    {
        // --- 1. SETTINGS & CONSTANTS (Live from DB) ---
        $settings = PlatformFinancialSetting::all()->pluck('value', 'key');
        $fixedCost = (float) ($settings['fixed_monthly_platform_cost'] ?? 450000);
        $varCloudVeh = (float) ($settings['variable_cloud_cost_per_vehicle'] ?? 150);
        $varSmsVeh = (float) ($settings['variable_sms_cost_per_vehicle'] ?? 50);
        $cacPerCompany = (float) ($settings['acquisition_cost_per_company'] ?? 2000);
        $cashBalance = (float) ($settings['current_cash_balance'] ?? 0);

        // --- 2. REVENUE CALCULATIONS (Real Aggregates) ---
        $activeSubs = Subscription::where('status', 'active')->get();
        $mrr = 0;
        foreach ($activeSubs as $sub) {
            $price = (float) $sub->price;
            $mrr += ($sub->billing_cycle === 'yearly') ? ($price / 12) : $price;
        }
        $arr = $mrr * 12;

        // MRR Breakdown by Tier
        $revenueByTier = Subscription::query()
            ->selectRaw('plan_name, sum(case when billing_cycle = "yearly" then price/12 else price end) as mrr_total, count(*) as count')
            ->where('status', 'active')
            ->groupBy('plan_name')
            ->get();

        // Expansion Revenue: MRR from customers who existed last month but are paying more this month
        $lastMonth = now()->subMonth();
        $expansionRevenue = Subscription::where('status', 'active')
            ->where('created_at', '<', $lastMonth->startOfMonth())
            ->where('updated_at', '>=', now()->startOfMonth())
            ->whereColumn('updated_at', '>', 'created_at')
            ->sum(DB::raw('price - (SELECT price FROM subscriptions as old WHERE old.id = subscriptions.id AND old.updated_at < "' . now()->startOfMonth() . '")'));
        
        $expansionRevenue = max(0, (float) $expansionRevenue);

        // Churn Trend (Real 4-month historical cancellation rates)
        $churnTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $churnedInMonth = Subscription::where('status', 'canceled')
                ->whereYear('updated_at', $month->year)
                ->whereMonth('updated_at', $month->month)
                ->count();
            $activeAtMonthStart = max(1, Subscription::where('created_at', '<', $month->startOfMonth())
                ->where(function($q) use ($month) {
                    $q->whereNull('updated_at')->orWhere('updated_at', '>=', $month->startOfMonth());
                })->count());
            $churnTrend[] = round(($churnedInMonth / $activeAtMonthStart) * 100, 2);
        }

        // New Companies (L30D)
        $newCompaniesCount = Company::where('created_at', '>=', now()->subDays(30))->count();

        // --- 3. OPERATIONAL & FLEET DATA ---
        $activeVehicles = Truck::where('status', 'Active')->count();
        $totalVehicles = Truck::count();
        $apiUsageToday = AiUsageLog::whereDate('created_at', now())->count();
        $apiUsageTotal = AiUsageLog::count();

        // Fleet Growth (L30D)
        $prevActiveVehicles = Truck::where('status', 'Active')
            ->where('created_at', '<=', now()->subDays(30))
            ->count();
        $fleetGrowth = $prevActiveVehicles > 0 ? round((($activeVehicles - $prevActiveVehicles) / $prevActiveVehicles) * 100, 1) : 0;

        // --- 4. MARGIN & RUNWAY CALCULATIONS ---
        $totalMonthlyBurn = $fixedCost + ($activeVehicles * ($varCloudVeh + $varSmsVeh));
        $netProfit = $mrr - $totalMonthlyBurn;
        
        $grossProfit = $mrr - ($activeVehicles * ($varCloudVeh + $varSmsVeh));
        $grossMargin = $mrr > 0 ? round(($grossProfit / $mrr) * 100, 1) : 0;
        $netMargin = $mrr > 0 ? round(($netProfit / $mrr) * 100, 1) : 0;

        $breakEvenProgress = $totalMonthlyBurn > 0 ? min(100, round(($mrr / $totalMonthlyBurn) * 100, 1)) : 100;
        
        // Gross Margin Trend (Last 4 months)
        $grossMarginTrend = [];
        for ($i = 3; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $monthEnd = $month->endOfMonth();
            
            // MRR at month end
            $monthMrr = Subscription::where('created_at', '<=', $monthEnd)
                ->where(function($q) use ($monthEnd) {
                    $q->where('status', 'active')
                      ->orWhere('updated_at', '>=', $monthEnd);
                })
                ->sum(DB::raw('case when billing_cycle = "yearly" then price/12 else price end'));
            
            // Vehicles at month end
            $monthVehicles = Truck::where('created_at', '<=', $monthEnd)
                 ->where(function($q) use ($monthEnd) {
                     $q->where('status', 'Active')
                       ->orWhere('updated_at', '>=', $monthEnd);
                 })->count();
            
            $monthGrossProfit = $monthMrr - ($monthVehicles * ($varCloudVeh + $varSmsVeh));
            $grossMarginTrend[] = $monthMrr > 0 ? round(($monthGrossProfit / $monthMrr) * 100, 1) : 0;
        }

        // Cash Runway: (Cash Balance / Monthly Net Loss)
        if ($netProfit >= 0) {
            $cashRunway = '∞ (Profitable)';
        } else {
            $monthlyLoss = abs($netProfit);
            $cashRunway = $monthlyLoss > 0 ? round($cashBalance / $monthlyLoss, 1) : '∞';
        }

        // --- 5. BOARD REPORTING & COMPARISONS ---
        $lastQuarterEnd = now()->subMonths(3)->endOfQuarter();
        $prevMrr = Subscription::where('status', 'active')
            ->where('created_at', '<=', $lastQuarterEnd)
            ->sum(DB::raw('case when billing_cycle = "yearly" then price/12 else price end'));
        $mrrGrowth = $prevMrr > 0 ? round((($mrr - $prevMrr) / $prevMrr) * 100, 1) : 100;

        // Unit Economics
        $totalActive = max(1, $activeSubs->count());
        $churnRate = $churnTrend[3]; // Current month
        $arpu = $totalActive > 0 ? round($mrr / $totalActive, 2) : 0;
        $ltv = $churnRate > 0 ? round($arpu / ($churnRate / 100), 2) : ($arpu * 12);
        $ltvCacRatio = $cacPerCompany > 0 ? round($ltv / $cacPerCompany, 2) : 0;

        // Health Score
        $healthScore = round(
            ($grossMargin * 0.4) + 
            (max(0, (100 - ($churnRate * 10))) * 0.3) + 
            (min(100, $ltvCacRatio * 10) * 0.3)
        );

        return [
            'mrr' => $mrr,
            'prevMrr' => $prevMrr,
            'mrrGrowth' => $mrrGrowth,
            'arr' => $arr,
            'activeVehicles' => $activeVehicles,
            'totalVehicles' => $totalVehicles,
            'fleetGrowth' => $fleetGrowth,
            'newCompaniesCount' => $newCompaniesCount,
            'grossMargin' => $grossMargin,
            'netMargin' => $netMargin,
            'netProfit' => $netProfit,
            'breakEvenProgress' => $breakEvenProgress,
            'breakEvenVehicles' => ceil($totalMonthlyBurn / max(1, ($mrr/max(1, $activeVehicles)))),
            'cashRunway' => $cashRunway,
            'healthScore' => $healthScore,
            'revenueByTier' => $revenueByTier,
            'expansionRevenue' => $expansionRevenue,
            'churnTrend' => $churnTrend,
            'grossMarginTrend' => $grossMarginTrend,
            'apiUsageToday' => $apiUsageToday,
            'apiUsageTotal' => $apiUsageTotal,
            'fixedMonthlyCost' => $fixedCost,
            'varCloudCost' => $activeVehicles * $varCloudVeh,
            'varSmsCost' => $activeVehicles * $varSmsVeh,
            'variableCostPerVehicle' => ($varCloudVeh + $varSmsVeh),
            'revenuePerVehicle' => $activeVehicles > 0 ? round($mrr / $activeVehicles, 2) : 0,
            'churnRate' => $churnRate,
            'arpu' => $arpu,
            'ltv' => $ltv,
            'ltvCacRatio' => $ltvCacRatio,
            'cac' => $cacPerCompany,
        ];
    }
}
