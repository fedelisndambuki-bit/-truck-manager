<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaintenanceWorkOrder extends EditRecord
{
    protected static string $resource = MaintenanceWorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
