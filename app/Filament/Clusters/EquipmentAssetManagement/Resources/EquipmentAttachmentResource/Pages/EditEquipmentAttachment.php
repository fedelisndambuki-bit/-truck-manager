<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAttachmentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEquipmentAttachment extends EditRecord
{
    protected static string $resource = EquipmentAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
