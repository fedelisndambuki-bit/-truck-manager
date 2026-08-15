<?php

namespace App\Filament\Resources\MaterialConsumptionResource\Pages;

use App\Filament\Resources\MaterialConsumptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialConsumptions extends ListRecords
{
    protected static string $resource = MaterialConsumptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Record Consumption'),
        ];
    }
}
