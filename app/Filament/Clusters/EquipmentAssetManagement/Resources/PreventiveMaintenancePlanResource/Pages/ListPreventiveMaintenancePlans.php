<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\PreventiveMaintenancePlanResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\PreventiveMaintenancePlanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPreventiveMaintenancePlans extends ListRecords
{
    protected static string $resource = PreventiveMaintenancePlanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
