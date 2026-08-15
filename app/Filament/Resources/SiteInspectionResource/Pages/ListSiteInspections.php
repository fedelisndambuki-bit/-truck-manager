<?php

namespace App\Filament\Resources\SiteInspectionResource\Pages;

use App\Filament\Resources\SiteInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSiteInspections extends ListRecords
{
    protected static string $resource = SiteInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
