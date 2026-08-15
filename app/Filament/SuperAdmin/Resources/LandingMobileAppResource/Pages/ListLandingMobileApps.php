<?php

namespace App\Filament\SuperAdmin\Resources\LandingMobileAppResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingMobileAppResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingMobileApps extends ListRecords
{
    protected static string $resource = LandingMobileAppResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
