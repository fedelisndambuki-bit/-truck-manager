<?php

namespace App\Filament\Resources\MaterialReturnResource\Pages;

use App\Filament\Resources\MaterialReturnResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialReturns extends ListRecords
{
    protected static string $resource = MaterialReturnResource::class;

    protected static string $view = 'filament.resources.material-return-resource.pages.list-material-returns';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Return'),
        ];
    }
}
