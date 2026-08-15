<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantOperationResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantOperationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlantOperations extends ListRecords
{
    protected static string $resource = PlantOperationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
