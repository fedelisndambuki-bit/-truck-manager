<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\DocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\DocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDocuments extends ListRecords
{
    protected static string $resource = DocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
