<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\ScreenResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\ScreenResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScreen extends EditRecord
{
    protected static string $resource = ScreenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
