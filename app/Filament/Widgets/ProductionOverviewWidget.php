<?php

namespace App\Filament\Widgets;

use App\Models\ExtractionSite;
use App\Models\ProductionLog;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProductionOverviewWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id;

        $tonnageThisMonth = ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('quantity_tonnes');

        $activeSites = ExtractionSite::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Active')
            ->count();

        $logsThisMonth = ProductionLog::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->count();

        return [
            Stat::make('Tonnage This Month', number_format((float) $tonnageThisMonth, 2) . ' t')
                ->description('Total production logged')
                ->descriptionIcon('heroicon-m-cube')
                ->color('success'),
            Stat::make('Active Extraction Sites', (string) $activeSites)
                ->description('Currently operating')
                ->descriptionIcon('heroicon-m-map')
                ->color('info'),
            Stat::make('Production Entries', (string) $logsThisMonth)
                ->description('Logged this month')
                ->descriptionIcon('heroicon-m-clipboard-document-list')
                ->color('gray'),
        ];
    }
}
