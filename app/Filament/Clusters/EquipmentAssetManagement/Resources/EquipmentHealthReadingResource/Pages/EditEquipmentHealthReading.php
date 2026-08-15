<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentHealthReadingResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentHealthReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentHealthReading extends EditRecord
{
    protected static string $resource = EquipmentHealthReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
