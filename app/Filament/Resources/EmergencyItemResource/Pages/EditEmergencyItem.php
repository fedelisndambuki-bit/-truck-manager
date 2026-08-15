<?php

namespace App\Filament\Resources\EmergencyItemResource\Pages;

use App\Filament\Resources\EmergencyItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditEmergencyItem extends EditRecord
{
    protected static string $resource = EmergencyItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
