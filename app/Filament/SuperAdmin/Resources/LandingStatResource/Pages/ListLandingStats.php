<?php

namespace App\Filament\SuperAdmin\Resources\LandingStatResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingStatResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingStats extends ListRecords
{
    protected static string $resource = LandingStatResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
