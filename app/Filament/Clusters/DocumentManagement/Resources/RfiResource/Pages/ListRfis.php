<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\RfiResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\RfiResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRfis extends ListRecords
{
    protected static string $resource = RfiResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
