<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ProductionRunResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionRun extends EditRecord
{
    protected static string $resource = ProductionRunResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
