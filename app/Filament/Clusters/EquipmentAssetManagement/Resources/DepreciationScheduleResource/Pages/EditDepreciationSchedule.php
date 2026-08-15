<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource\Pages;

use App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDepreciationSchedule extends EditRecord
{
    protected static string $resource = DepreciationScheduleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
