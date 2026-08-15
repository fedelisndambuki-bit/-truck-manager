<?php

namespace App\Filament\Resources\MaterialConsumptionResource\Pages;

use App\Filament\Resources\MaterialConsumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditMaterialConsumption extends EditRecord
{
    protected static string $resource = MaterialConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
