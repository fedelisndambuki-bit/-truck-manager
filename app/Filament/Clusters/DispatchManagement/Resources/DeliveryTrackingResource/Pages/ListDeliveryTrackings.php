<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\DeliveryTrackingResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\DeliveryTrackingResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListDeliveryTrackings extends ListRecords
{
    protected static string $resource = DeliveryTrackingResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-map-pin',
            'title' => 'Delivery Tracking',
            'subtitle' => 'Monitor real-time location and status of in-progress deliveries.',
        ]);
    }
}
