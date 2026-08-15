<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\FuelLogResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\FuelLogResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditFuelLog extends EditRecord
{
    protected static string $resource = FuelLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
