<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\CorrectiveMaintenanceResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\CorrectiveMaintenanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCorrectiveMaintenance extends ListRecords
{
    protected static string $resource = CorrectiveMaintenanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
