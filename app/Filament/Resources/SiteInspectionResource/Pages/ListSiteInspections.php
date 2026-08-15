<?php

namespace App\Filament\Resources\SiteInspectionResource\Pages;

use App\Filament\Resources\SiteInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteInspections extends ListRecords
{
    protected static string $resource = SiteInspectionResource::class;

    protected static string $view = 'filament.resources.site-inspection-resource.pages.list-site-inspections';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
