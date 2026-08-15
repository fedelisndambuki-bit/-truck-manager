<?php

namespace App\Filament\SuperAdmin\Resources\LandingNavigationResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingNavigationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingNavigation extends EditRecord
{
    protected static string $resource = LandingNavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
