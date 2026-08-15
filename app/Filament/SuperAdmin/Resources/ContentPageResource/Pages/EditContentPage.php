<?php

namespace App\Filament\SuperAdmin\Resources\ContentPageResource\Pages;

use App\Filament\SuperAdmin\Resources\ContentPageResource;
use Filament\Resources\Pages\EditRecord;

class EditContentPage extends EditRecord
{
    protected static string $resource = ContentPageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
