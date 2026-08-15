<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentDocuments extends ListRecords
{
    protected static string $resource = EquipmentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
