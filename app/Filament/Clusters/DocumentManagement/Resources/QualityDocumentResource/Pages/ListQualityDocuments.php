<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\QualityDocumentResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\QualityDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQualityDocuments extends ListRecords
{
    protected static string $resource = QualityDocumentResource::class;

    protected static string $view = 'filament.clusters.document-management.resources.quality-document-resource.pages.list-quality-documents';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
