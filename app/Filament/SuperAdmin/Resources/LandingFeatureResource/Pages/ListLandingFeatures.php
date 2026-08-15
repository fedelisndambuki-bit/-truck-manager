<?php

namespace App\Filament\SuperAdmin\Resources\LandingFeatureResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingFeatureResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingFeatures extends ListRecords
{
    protected static string $resource = LandingFeatureResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
