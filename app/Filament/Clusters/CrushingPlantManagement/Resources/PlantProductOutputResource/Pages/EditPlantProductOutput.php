<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantProductOutputResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantProductOutputResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPlantProductOutput extends EditRecord
{
    protected static string $resource = PlantProductOutputResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
