<?php

namespace App\Filament\SuperAdmin\Resources\ContentPageResource\Pages;

use App\Filament\SuperAdmin\Resources\ContentPageResource;
use Filament\Resources\Pages\ListRecords;

class ListContentPages extends ListRecords
{
    protected static string $resource = ContentPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
