<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Widgets;

use App\Models\MaintenanceWorkOrder;
use App\Models\WorkshopBay;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class WorkshopCapacityWidget extends BaseWidget
{
    protected function getColumns(): int
    {
        return 4;
    }

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $bays = WorkshopBay::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));
        $jobs = MaintenanceWorkOrder::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $totalBays = (clone $bays)->count();
        $availableBays = (clone $bays)->where('status', 'Available')->count();
        $occupiedBays = (clone $bays)->where('status', 'Occupied')->count();
        $openJobs = (clone $jobs)->whereNotIn('status', ['Closed'])->count();
        $waitingParts = (clone $jobs)->where('status', 'Waiting Parts')->count();

        return [
            Stat::make('Total Bays', (string) $totalBays)->descriptionIcon('heroicon-m-squares-2x2')->color('info'),
            Stat::make('Available Bays', (string) $availableBays)->descriptionIcon('heroicon-m-check-circle')->color('success'),
            Stat::make('Occupied Bays', (string) $occupiedBays)->descriptionIcon('heroicon-m-wrench')->color($occupiedBays > 0 ? 'warning' : 'success'),
            Stat::make('Open Jobs', (string) $openJobs)->descriptionIcon('heroicon-m-clipboard-document-list')->color($openJobs > 0 ? 'warning' : 'success'),
            Stat::make('Waiting on Parts', (string) $waitingParts)->descriptionIcon('heroicon-m-cube')->color($waitingParts > 0 ? 'danger' : 'success'),
        ];
    }
}
