<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\ContractDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\ContractDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListContractDocuments extends ListRecords
{
    protected static string $resource = ContractDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
