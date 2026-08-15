<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDepreciationSchedules extends ListRecords
{
    protected static string $resource = DepreciationScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
