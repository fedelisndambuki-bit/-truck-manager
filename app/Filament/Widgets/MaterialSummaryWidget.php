<?php

namespace App\Filament\Widgets;

use App\Models\Material;
use App\Models\MaterialDelivery;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MaterialSummaryWidget extends BaseWidget
{
    protected static ?int $sort = 7;

    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;

        $receivedToday = MaterialDelivery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereDate('delivered_at', today())
            ->where('status', 'Delivered')
            ->count();

        $lowStock = Material::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereColumn('quantity_on_hand', '<', 'reorder_level')
            ->count();

        $pendingPOs = MaterialDelivery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Pending')
            ->count();

        $consumption = MaterialDelivery::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->where('status', 'Delivered')
            ->whereMonth('delivered_at', now()->month)
            ->sum('quantity');

        return [
            Stat::make('Materials Received Today', (string) $receivedToday)->color('success'),
            Stat::make('Low Stock Items', (string) $lowStock)->color($lowStock > 0 ? 'danger' : 'success'),
            Stat::make('Pending Purchase Orders', (string) $pendingPOs)->color('warning'),
            Stat::make('Consumption (This Month)', number_format((float) $consumption, 0))->color('info'),
        ];
    }
}
