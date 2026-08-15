<?php

namespace App\Filament\Resources\EmergencyItemResource\Pages;

use App\Filament\Resources\EmergencyItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmergencyItems extends ListRecords
{
    protected static string $resource = EmergencyItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
