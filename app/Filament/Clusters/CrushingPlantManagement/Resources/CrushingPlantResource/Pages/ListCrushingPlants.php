<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCrushingPlants extends ListRecords
{
    protected static string $resource = CrushingPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
