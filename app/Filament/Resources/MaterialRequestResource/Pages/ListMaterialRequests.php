<?php

namespace App\Filament\Resources\MaterialRequestResource\Pages;

use App\Filament\Resources\MaterialRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMaterialRequests extends ListRecords
{
    protected static string $resource = MaterialRequestResource::class;

    protected static string $view = 'filament.resources.material-request-resource.pages.list-material-requests';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Request'),
        ];
    }
}
