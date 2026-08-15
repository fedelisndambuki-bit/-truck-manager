<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditVehicleCheckIn extends EditRecord
{
    protected static string $resource = VehicleCheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
