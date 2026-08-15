<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantOperationResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantOperation extends EditRecord
{
    protected static string $resource = PlantOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
