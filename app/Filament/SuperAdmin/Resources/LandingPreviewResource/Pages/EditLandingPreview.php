<?php

namespace App\Filament\SuperAdmin\Resources\LandingPreviewResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingPreviewResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingPreview extends EditRecord
{
    protected static string $resource = LandingPreviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
