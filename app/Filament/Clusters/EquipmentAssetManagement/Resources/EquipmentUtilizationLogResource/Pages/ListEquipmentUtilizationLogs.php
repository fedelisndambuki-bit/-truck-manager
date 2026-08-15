<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentUtilizationLogResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentUtilizationLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentUtilizationLogs extends ListRecords
{
    protected static string $resource = EquipmentUtilizationLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
