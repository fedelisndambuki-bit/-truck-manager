<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkshop extends EditRecord
{
    protected static string $resource = WorkshopResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
