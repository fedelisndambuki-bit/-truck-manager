<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentHealthReadingResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentHealthReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentHealthReadings extends ListRecords
{
    protected static string $resource = EquipmentHealthReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
