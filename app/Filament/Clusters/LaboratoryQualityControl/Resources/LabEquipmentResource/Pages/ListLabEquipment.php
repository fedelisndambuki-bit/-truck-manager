<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabEquipment extends ListRecords
{
    protected static string $resource = LabEquipmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
