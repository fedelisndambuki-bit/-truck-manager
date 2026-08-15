<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAttachmentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAttachmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentAttachments extends ListRecords
{
    protected static string $resource = EquipmentAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
