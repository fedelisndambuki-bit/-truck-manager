<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\CrusherResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\CrusherResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCrusher extends EditRecord
{
    protected static string $resource = CrusherResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
