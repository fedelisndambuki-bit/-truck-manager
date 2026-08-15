<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVehicleCheckIns extends ListRecords
{
    protected static string $resource = VehicleCheckInResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
