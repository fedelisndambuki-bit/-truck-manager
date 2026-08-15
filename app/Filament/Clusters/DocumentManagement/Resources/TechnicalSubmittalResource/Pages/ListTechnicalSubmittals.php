<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\TechnicalSubmittalResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\TechnicalSubmittalResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTechnicalSubmittals extends ListRecords
{
    protected static string $resource = TechnicalSubmittalResource::class;

    protected static string $view = 'filament.clusters.document-management.resources.technical-submittal-resource.pages.list-technical-submittals';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
