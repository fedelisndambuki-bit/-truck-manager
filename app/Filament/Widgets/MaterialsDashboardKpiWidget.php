<?php

namespace App\Filament\Widgets;

use App\Models\Material;
use App\Models\MaterialConsumption;
use App\Models\MaterialDelivery;
use App\Models\MaterialRequest;
use App\Models\MaterialWastage;
use App\Models\BinLocation;
use App\Models\PhysicalStockCountItem;
use App\Models\PutAwayTask;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class MaterialsDashboardKpiWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $companyId = Auth::user()?->company_id;
        $materials = Material::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId));

        $total = (clone $materials)->count();
        $active = (clone $materials)->where('status', 'Active')->count();
        $totalValue = (clone $materials)->get()->sum(fn (Material $m) => $m->inventory_value);

        $lowStock = (clone $materials)->whereColumn('quantity_on_hand', '<=', 'reorder_level')->where('quantity_on_hand', '>', 0)->count();
        $outOfStock = (clone $materials)->where('quantity_on_hand', '<=', 0)->count();

        $pendingDeliveries = MaterialDelivery::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Pending')->count();
        $deliveriesToday = MaterialDelivery::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereDate('delivered_at', today())->count();

        $pendingRequests = MaterialRequest::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Pending')->count();

        $consumptionToday = MaterialConsumption::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereDate('date', today())->sum('quantity_used');
        $wastageToday = MaterialWastage::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->whereDate('created_at', today())->sum('quantity');

        $countedItems = PhysicalStockCountItem::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereNotNull('counted_quantity')
            ->get();
        $accurateItems = $countedItems->filter(fn ($item) => abs((float) $item->variance) < 0.01)->count();
        $stockAccuracy = $countedItems->count() > 0 ? round(($accurateItems / $countedItems->count()) * 100) : 100;

        $monthlyCost = MaterialConsumption::query()
            ->when($companyId, fn ($q) => $q->where('company_id', $companyId))
            ->whereMonth('date', now()->month)
            ->whereYear('date', now()->year)
            ->sum('cost');

        $binLocations = BinLocation::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->count();
        $pendingPutAway = PutAwayTask::query()->when($companyId, fn ($q) => $q->where('company_id', $companyId))->where('status', 'Pending')->count();

        return [
            Stat::make('Total Materials', (string) $total)->color('info'),
            Stat::make('Active Materials', (string) $active)->color('success'),
            Stat::make('Total Inventory Value', session('currency', 'KES') . ' ' . number_format($totalValue, 0))->color('success'),
            Stat::make('Low Stock Items', (string) $lowStock)->color($lowStock > 0 ? 'warning' : 'success'),
            Stat::make('Out of Stock Items', (string) $outOfStock)->color($outOfStock > 0 ? 'danger' : 'success'),
            Stat::make('Pending Deliveries', (string) $pendingDeliveries)->color($pendingDeliveries > 0 ? 'warning' : 'success'),
            Stat::make('Deliveries Today', (string) $deliveriesToday)->color('info'),
            Stat::make('Requests Pending Approval', (string) $pendingRequests)->color($pendingRequests > 0 ? 'warning' : 'success'),
            Stat::make('Consumption Today', number_format($consumptionToday, 2))->color('info'),
            Stat::make('Wastage Today', number_format($wastageToday, 2))->color($wastageToday > 0 ? 'danger' : 'success'),
            Stat::make('Stock Accuracy', $stockAccuracy . '%')->color($stockAccuracy >= 95 ? 'success' : 'warning'),
            Stat::make('Monthly Material Cost', session('currency', 'KES') . ' ' . number_format($monthlyCost, 0))->color('success'),
            Stat::make('Bin Locations', (string) $binLocations)->color('info'),
            Stat::make('Pending Put-Away', (string) $pendingPutAway)->color($pendingPutAway > 0 ? 'warning' : 'success'),
        ];
    }
}
