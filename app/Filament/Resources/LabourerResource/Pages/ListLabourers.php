<?php

namespace App\Filament\Resources\LabourerResource\Pages;

use App\Filament\Resources\LabourerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabourers extends ListRecords
{
    protected static string $resource = LabourerResource::class;

    protected static string $view = 'filament.resources.labourer-resource.pages.list-labourers';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
