<?php

namespace App\Filament\SuperAdmin\Resources\LandingMobileAppResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingMobileAppResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingMobileApp extends EditRecord
{
    protected static string $resource = LandingMobileAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
