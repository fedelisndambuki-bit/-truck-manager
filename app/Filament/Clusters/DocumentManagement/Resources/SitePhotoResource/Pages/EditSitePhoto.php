<?php

namespace App\Filament\Clusters\DocumentManagement\Resources\SitePhotoResource\Pages;

use App\Filament\Clusters\DocumentManagement\Resources\SitePhotoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSitePhoto extends EditRecord
{
    protected static string $resource = SitePhotoResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
