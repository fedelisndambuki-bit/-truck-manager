<?php

namespace App\Filament\Resources\SafetyDocumentResource\Pages;

use App\Filament\Resources\SafetyDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSafetyDocuments extends ListRecords
{
    protected static string $resource = SafetyDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
