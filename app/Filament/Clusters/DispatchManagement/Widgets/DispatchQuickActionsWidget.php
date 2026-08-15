<?php

namespace App\Filament\Clusters\DispatchManagement\Widgets;

use App\Filament\Clusters\DispatchManagement\Resources\CustomerDeliveryResource;
use App\Filament\Clusters\DispatchManagement\Resources\CustomerOrderResource;
use App\Filament\Clusters\DispatchManagement\Resources\DeliveryTrackingResource;
use App\Filament\Clusters\DispatchManagement\Resources\ProofOfDeliveryResource;
use App\Filament\Clusters\DispatchManagement\Resources\SchedulingResource;
use Filament\Widgets\Widget;

class DispatchQuickActionsWidget extends Widget
{
    protected static string $view = 'filament.widgets.quarry.quick-actions';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 1;

    public function getActions(): array
    {
        return [
            ['label' => 'Plan Order', 'icon' => 'heroicon-o-clipboard-document-list', 'url' => CustomerOrderResource::getUrl('create')],
            ['label' => 'Schedule Dispatch', 'icon' => 'heroicon-o-calendar-days', 'url' => SchedulingResource::getUrl('index')],
            ['label' => 'Track Delivery', 'icon' => 'heroicon-o-map-pin', 'url' => DeliveryTrackingResource::getUrl('index')],
            ['label' => 'Confirm Delivery', 'icon' => 'heroicon-o-document-check', 'url' => ProofOfDeliveryResource::getUrl('create')],
            ['label' => 'Customer History', 'icon' => 'heroicon-o-building-storefront', 'url' => CustomerDeliveryResource::getUrl('index')],
            ['label' => 'Generate Report', 'icon' => 'heroicon-o-document-arrow-down', 'url' => \App\Filament\Clusters\DispatchManagement\Pages\Reports::getUrl()],
        ];
    }
}
