<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrushingPlantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrushingPlant extends EditRecord
{
    protected static string $resource = CrushingPlantResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
