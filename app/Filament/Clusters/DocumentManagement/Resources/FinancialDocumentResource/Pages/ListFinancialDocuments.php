<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\FinancialDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\FinancialDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFinancialDocuments extends ListRecords
{
    protected static string $resource = FinancialDocumentResource::class;

    protected static string $view = 'filament.clusters.document-management.resources.financial-document-resource.pages.list-financial-documents';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
