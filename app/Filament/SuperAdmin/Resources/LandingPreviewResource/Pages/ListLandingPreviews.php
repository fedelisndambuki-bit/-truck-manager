<?php

namespace App\Filament\SuperAdmin\Resources\LandingPreviewResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingPreviewResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingPreviews extends ListRecords
{
    protected static string $resource = LandingPreviewResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
