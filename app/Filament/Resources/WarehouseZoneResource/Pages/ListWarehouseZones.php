<?php

namespace App\Filament\Resources\WarehouseZoneResource\Pages;

use App\Filament\Resources\WarehouseZoneResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWarehouseZones extends ListRecords
{
    protected static string $resource = WarehouseZoneResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
