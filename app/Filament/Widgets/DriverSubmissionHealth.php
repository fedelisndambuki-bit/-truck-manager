<?php

namespace App\Filament\Widgets;

use App\Models\DriverFuelReceipt;
use App\Models\DriverPod;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class DriverSubmissionHealth extends BaseWidget
{
    protected static ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $user = auth()->user();
        $scopeCompany = $user && ! $user->hasRole('Super Admin');

        $receiptPending = DriverFuelReceipt::query()
            ->where('status', 'pending')
            ->when($scopeCompany, fn ($q) => $q->whereHas('driver', fn ($d) => $d->where('company_id', $user->company_id)))
            ->count();

        $receiptFailed = DriverFuelReceipt::query()
            ->where('status', 'failed')
            ->when($scopeCompany, fn ($q) => $q->whereHas('driver', fn ($d) => $d->where('company_id', $user->company_id)))
            ->count();

        $podPending = DriverPod::query()
            ->where('status', 'pending')
            ->when($scopeCompany, fn ($q) => $q->whereHas('driver', fn ($d) => $d->where('company_id', $user->company_id)))
            ->count();

        $podFailed = DriverPod::query()
            ->where('status', 'failed')
            ->when($scopeCompany, fn ($q) => $q->whereHas('driver', fn ($d) => $d->where('company_id', $user->company_id)))
            ->count();

        return [
            Stat::make('Receipt Submissions Pending', $receiptPending)
                ->description('Fuel receipts awaiting review')
                ->descriptionIcon('heroicon-s-clock')
                ->color($receiptPending > 0 ? 'warning' : 'success'),

            Stat::make('Receipt Submissions Failed', $receiptFailed)
                ->description('Fuel receipts marked as failed')
                ->descriptionIcon('heroicon-s-exclamation-triangle')
                ->color($receiptFailed > 0 ? 'danger' : 'success'),

            Stat::make('POD Submissions Pending', $podPending)
                ->description('Proof of delivery awaiting review')
                ->descriptionIcon('heroicon-s-clipboard-document-check')
                ->color($podPending > 0 ? 'warning' : 'success'),

            Stat::make('POD Submissions Failed', $podFailed)
                ->description('PODs marked as failed')
                ->descriptionIcon('heroicon-s-x-circle')
                ->color($podFailed > 0 ? 'danger' : 'success'),
        ];
    }
}
