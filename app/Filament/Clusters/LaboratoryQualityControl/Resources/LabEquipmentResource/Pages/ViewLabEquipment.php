<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLabEquipment extends ViewRecord
{
    protected static string $resource = LabEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
