<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionDowntimeResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionDowntimeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionDowntime extends EditRecord
{
    protected static string $resource = ProductionDowntimeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
