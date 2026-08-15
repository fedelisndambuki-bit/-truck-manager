<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastDocumentResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlastDocuments extends ListRecords
{
    protected static string $resource = BlastDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
