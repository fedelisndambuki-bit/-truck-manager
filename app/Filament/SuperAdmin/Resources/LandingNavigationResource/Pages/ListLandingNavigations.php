<?php

namespace App\Filament\SuperAdmin\Resources\LandingNavigationResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingNavigationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingNavigations extends ListRecords
{
    protected static string $resource = LandingNavigationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
