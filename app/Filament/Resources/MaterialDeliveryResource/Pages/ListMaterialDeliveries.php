<?php

namespace App\Filament\Resources\MaterialDeliveryResource\Pages;

use App\Filament\Resources\MaterialDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialDeliveries extends ListRecords
{
    protected static string $resource = MaterialDeliveryResource::class;

    protected static string $view = 'filament.resources.material-delivery-resource.pages.list-material-deliveries';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Delivery'),
        ];
    }
}
