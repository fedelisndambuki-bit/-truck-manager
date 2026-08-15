<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAllocationResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentAllocationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEquipmentAllocations extends ListRecords
{
    protected static string $resource = EquipmentAllocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Assign Equipment'),
        ];
    }
}
