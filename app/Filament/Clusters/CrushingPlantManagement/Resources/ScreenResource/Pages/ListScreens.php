<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ScreenResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ScreenResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScreens extends ListRecords
{
    protected static string $resource = ScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
