<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMaintenanceWorkOrder extends CreateRecord
{
    protected static string $resource = MaintenanceWorkOrderResource::class;
}
