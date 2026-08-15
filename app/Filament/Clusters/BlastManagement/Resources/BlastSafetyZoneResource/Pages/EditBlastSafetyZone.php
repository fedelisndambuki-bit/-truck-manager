<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastSafetyZoneResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastSafetyZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBlastSafetyZone extends EditRecord
{
    protected static string $resource = BlastSafetyZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
