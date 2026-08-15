<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopBayResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\WorkshopBayResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWorkshopBay extends EditRecord
{
    protected static string $resource = WorkshopBayResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
