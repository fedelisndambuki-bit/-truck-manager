<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\PartResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\PartResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListParts extends ListRecords
{
    protected static string $resource = PartResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
