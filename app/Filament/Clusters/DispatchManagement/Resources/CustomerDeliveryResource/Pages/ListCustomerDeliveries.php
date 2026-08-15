<?php

namespace App\Filament\Clusters\DispatchManagement\Resources\CustomerDeliveryResource\Pages;

use App\Filament\Clusters\DispatchManagement\Resources\CustomerDeliveryResource;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\View\View;

class ListCustomerDeliveries extends ListRecords
{
    protected static string $resource = CustomerDeliveryResource::class;

    public function getHeader(): ?View
    {
        return view('filament.partials.page-header', [
            'icon' => 'heroicon-o-building-storefront',
            'title' => 'Customer Deliveries',
            'subtitle' => 'Track deliveries scheduled for customers across your fleet.',
        ]);
    }
}
