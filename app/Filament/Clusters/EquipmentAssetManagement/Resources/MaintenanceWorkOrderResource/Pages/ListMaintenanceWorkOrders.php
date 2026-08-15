<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaintenanceWorkOrders extends ListRecords
{
    protected static string $resource = MaintenanceWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
