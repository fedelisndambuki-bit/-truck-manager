<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ConveyorResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ConveyorResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditConveyor extends EditRecord
{
    protected static string $resource = ConveyorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
