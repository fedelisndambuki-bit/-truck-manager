<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\EnergyReadingResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\EnergyReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEnergyReading extends EditRecord
{
    protected static string $resource = EnergyReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
