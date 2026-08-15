<?php

namespace App\Filament\Clusters\StockpileManagement\Widgets;

use App\Filament\Clusters\StockpileManagement\Resources\LiveInventoryResource;
use App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource;
use App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource;
use App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource;
use App\Filament\Clusters\StockpileManagement\Resources\StockTransferResource;
use Filament\Widgets\Widget;

class StockpileQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quarry.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Create Stock Transfer', 'icon' => 'heroicon-o-arrow-path', 'url' => StockTransferResource::getUrl('create')],
            ['label' => 'Reserve Stock', 'icon' => 'heroicon-o-bookmark', 'url' => StockReservationResource::getUrl('create')],
            ['label' => 'Record Adjustment', 'icon' => 'heroicon-o-adjustments-horizontal', 'url' => StockAdjustmentResource::getUrl('create')],
            ['label' => 'Start Physical Count', 'icon' => 'heroicon-o-clipboard-document-check', 'url' => PhysicalStockCountResource::getUrl('create')],
            ['label' => 'Release Reservation', 'icon' => 'heroicon-o-lock-open', 'url' => StockReservationResource::getUrl('index')],
            ['label' => 'View Inventory', 'icon' => 'heroicon-o-list-bullet', 'url' => LiveInventoryResource::getUrl('index')],
            ['label' => 'Generate Stock Report', 'icon' => 'heroicon-o-document-arrow-down', 'url' => \App\Filament\Clusters\StockpileManagement\Pages\Reports::getUrl()],
        ];
    }
}
