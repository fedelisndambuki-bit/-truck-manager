<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantProductOutputResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantProductOutputResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlantProductOutputs extends ListRecords
{
    protected static string $resource = PlantProductOutputResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
