<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\SitePhotoResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\SitePhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSitePhotos extends ListRecords
{
    protected static string $resource = SitePhotoResource::class;

    protected static string $view = 'filament.clusters.document-management.resources.site-photo-resource.pages.list-site-photos';

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
