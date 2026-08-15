<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\BoqDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\BoqDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBoqDocuments extends ListRecords
{
    protected static string $resource = BoqDocumentResource::class;

    protected static string $view = 'filament.clusters.document-management.resources.boq-document-resource.pages.list-boq-documents';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
