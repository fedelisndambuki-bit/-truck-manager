<?php

namespace App\Filament\SuperAdmin\Resources\LandingSectionResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingSectionResource;
use Filament\Resources\Pages\ListRecords;

class ListLandingSections extends ListRecords
{
    protected static string $resource = LandingSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
