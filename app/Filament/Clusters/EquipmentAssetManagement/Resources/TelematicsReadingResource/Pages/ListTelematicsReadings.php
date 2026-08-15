<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\TelematicsReadingResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\TelematicsReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTelematicsReadings extends ListRecords
{
    protected static string $resource = TelematicsReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
