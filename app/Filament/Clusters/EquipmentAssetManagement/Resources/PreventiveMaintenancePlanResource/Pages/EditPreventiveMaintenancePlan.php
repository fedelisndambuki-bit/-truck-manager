<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\PreventiveMaintenancePlanResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\PreventiveMaintenancePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPreventiveMaintenancePlan extends EditRecord
{
    protected static string $resource = PreventiveMaintenancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
