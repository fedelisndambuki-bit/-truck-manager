<?php

namespace App\Filament\Resources\NearMissResource\Pages;

use App\Filament\Resources\NearMissResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNearMiss extends EditRecord
{
    protected static string $resource = NearMissResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
