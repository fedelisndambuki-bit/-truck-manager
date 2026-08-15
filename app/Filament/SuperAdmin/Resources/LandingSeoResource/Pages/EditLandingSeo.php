<?php

namespace App\Filament\SuperAdmin\Resources\LandingSeoResource\Pages;

use App\Filament\SuperAdmin\Resources\LandingSeoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLandingSeo extends EditRecord
{
    protected static string $resource = LandingSeoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
