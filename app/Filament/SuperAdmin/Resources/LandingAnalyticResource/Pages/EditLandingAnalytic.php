<?php

namespace App\Filament\SuperAdmin\Resources\LandingAnalyticResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingAnalyticResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingAnalytic extends EditRecord
{
    protected static string $resource = LandingAnalyticResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
