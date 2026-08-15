<?php

namespace App\Filament\SuperAdmin\Resources\LandingCtaResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingCtaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingCta extends EditRecord
{
    protected static string $resource = LandingCtaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
