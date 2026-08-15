<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLabEquipment extends EditRecord
{
    protected static string $resource = LabEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
