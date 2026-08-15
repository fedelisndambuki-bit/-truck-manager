<?php

namespace App\Filament\SuperAdmin\Resources\LandingHeroResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingHeroResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLandingHeroes extends ListRecords
{
    protected static string $resource = LandingHeroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
