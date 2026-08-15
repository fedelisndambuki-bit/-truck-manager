<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastSafetyZoneResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastSafetyZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlastSafetyZones extends ListRecords
{
    protected static string $resource = BlastSafetyZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
