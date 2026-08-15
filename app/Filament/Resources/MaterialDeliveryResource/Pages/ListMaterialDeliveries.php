<?php

namespace App\Filament\Resources\MaterialDeliveryResource\Pages;

use App\Filament\Resources\MaterialDeliveryResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialDeliveries extends ListRecords
{
    protected static string $resource = MaterialDeliveryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Delivery'),
        ];
    }
}
