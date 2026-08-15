<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentDocument extends EditRecord
{
    protected static string $resource = EquipmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
