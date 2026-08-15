<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\PlantDowntimeResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\PlantDowntimeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPlantDowntimes extends ListRecords
{
    protected static string $resource = PlantDowntimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
