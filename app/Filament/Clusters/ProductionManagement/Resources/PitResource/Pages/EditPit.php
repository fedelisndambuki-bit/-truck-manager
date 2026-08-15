<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\PitResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\PitResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPit extends EditRecord
{
    protected static string $resource = PitResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
