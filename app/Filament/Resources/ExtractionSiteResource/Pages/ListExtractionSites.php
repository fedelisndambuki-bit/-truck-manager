<?php

namespace App\Filament\Resources\ExtractionSiteResource\Pages;

use App\Filament\Resources\ExtractionSiteResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListExtractionSites extends ListRecords
{
    protected static string $resource = ExtractionSiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
