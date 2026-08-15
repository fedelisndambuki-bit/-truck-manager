<?php

namespace App\Filament\Resources\MaterialReturnResource\Pages;

use App\Filament\Resources\MaterialReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialReturns extends ListRecords
{
    protected static string $resource = MaterialReturnResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Return'),
        ];
    }
}
