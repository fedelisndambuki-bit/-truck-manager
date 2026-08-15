<?php

namespace App\Filament\SuperAdmin\Resources\LandingSectionResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingSectionResource;
use Filament\Resources\Pages\EditRecord;

class EditLandingSection extends EditRecord
{
    protected static string $resource = LandingSectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}
