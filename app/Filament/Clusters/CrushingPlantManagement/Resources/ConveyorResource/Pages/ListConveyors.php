<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ConveyorResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ConveyorResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListConveyors extends ListRecords
{
    protected static string $resource = ConveyorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
