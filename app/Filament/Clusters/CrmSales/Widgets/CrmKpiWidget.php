<?php

namespace App\Filament\Clusters\CrmSales\Widgets;

use App\Models\Client;
use App\Models\CustomerOrder;
use App\Models\Lead;
use App\Models\Opportunity;
use App\Models\Quotation;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CrmKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $currency = session('currency', 'KES');
        $fmt = fn (float $v) => $currency . ' ' . number_format($v, 0);

        $leads = Lead::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $newLeads = (clone $leads)->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count();
        $qualifiedLeads = (clone $leads)->where('status', 'Qualified')->count();

        $opportunities = Opportunity::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $openOpportunities = (clone $opportunities)->whereNotIn('stage', ['Won', 'Lost'])->count();
        $wonThisMonth = (clone $opportunities)->where('stage', 'Won')
            ->whereMonth('updated_at', now()->month)->whereYear('updated_at', now()->year)->count();

        $wonThisQuarter = (clone $opportunities)->where('stage', 'Won')->where('updated_at', '>=', now()->subMonths(3))->count();
        $lostThisQuarter = (clone $opportunities)->where('stage', 'Lost')->where('updated_at', '>=', now()->subMonths(3))->count();
        $conversionRate = ($wonThisQuarter + $lostThisQuarter) > 0
            ? round(($wonThisQuarter / ($wonThisQuarter + $lostThisQuarter)) * 100)
            : 0;

        $quotationValue = Quotation::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereIn('status', ['Draft', 'Submitted', 'Approved'])
            ->sum('total');

        $salesOrdersThisMonth = CustomerOrder::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)
            ->count();

        $outstandingReceivables = Client::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->get()
            ->sum(fn (Client $c) => $c->outstandingBalance());

        return [
            Stat::make('New Leads (This Month)', (string) $newLeads)->color('info'),
            Stat::make('Qualified Leads', (string) $qualifiedLeads)->color('info'),
            Stat::make('Open Opportunities', (string) $openOpportunities)->color('warning'),
            Stat::make('Won Deals (This Month)', (string) $wonThisMonth)->color('success'),
            Stat::make('Quotation Value (Open)', $fmt($quotationValue))->color('info'),
            Stat::make('Sales Orders (This Month)', (string) $salesOrdersThisMonth)->color('success'),
            Stat::make('Outstanding Receivables', $fmt($outstandingReceivables))->color($outstandingReceivables > 0 ? 'warning' : 'success'),
            Stat::make('Sales Conversion Rate', $conversionRate . '%')->color($conversionRate >= 50 ? 'success' : 'warning'),
        ];
    }
}
