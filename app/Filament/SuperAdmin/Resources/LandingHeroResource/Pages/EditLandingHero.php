<?php

namespace App\Filament\SuperAdmin\Resources\LandingHeroResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingHeroResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingHero extends EditRecord
{
    protected static string $resource = LandingHeroResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
