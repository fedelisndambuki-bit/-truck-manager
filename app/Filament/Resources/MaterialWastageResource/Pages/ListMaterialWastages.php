<?php

namespace App\Filament\Resources\MaterialWastageResource\Pages;

use App\Filament\Resources\MaterialWastageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialWastages extends ListRecords
{
    protected static string $resource = MaterialWastageResource::class;

    protected static string $view = 'filament.resources.material-wastage-resource.pages.list-material-wastages';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Log Wastage'),
        ];
    }
}
