<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentInspectionResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentInspection extends EditRecord
{
    protected static string $resource = EquipmentInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
