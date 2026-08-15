<?php

namespace App\Filament\Widgets;

use App\Models\DriverFuelReceipt;
use App\Models\DriverPod;
use App\Models\Expense;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PendingApprovalsWidget extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $companyId = auth()->user()?->company_id;

        $draftExpenses = ($companyId
            ? Expense::where('company_id', $companyId)
            : Expense::query())
            ->where('status', 'Draft')
            ->count();

        $pendingPods = ($companyId
            ? DriverPod::where('company_id', $companyId)
            : DriverPod::query())
            ->where('status', 'pending')
            ->count();

        $pendingFuelReceipts = DriverFuelReceipt::where('status', 'pending')
            ->when($companyId, fn ($q) => $q->whereHas('trip', fn ($t) => $t->where('company_id', $companyId)))
            ->count();

        return [
            Stat::make('Draft Expenses', $draftExpenses)
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-s-clock')
                ->color($draftExpenses > 0 ? 'warning' : 'success')
                ->url(route('filament.admin.resources.expenses.index', ['tableFilters[status][value]' => 'Draft'])),

            Stat::make('Pending PODs', $pendingPods)
                ->description('Proof of delivery to review')
                ->descriptionIcon('heroicon-s-document-check')
                ->color($pendingPods > 0 ? 'warning' : 'success'),

            Stat::make('Pending Fuel Receipts', $pendingFuelReceipts)
                ->description('Driver fuel submissions')
                ->descriptionIcon('heroicon-s-fire')
                ->color($pendingFuelReceipts > 0 ? 'warning' : 'success'),
        ];
    }
}
