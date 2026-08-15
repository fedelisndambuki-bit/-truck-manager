<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\CalibrationResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\CalibrationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCalibration extends EditRecord
{
    protected static string $resource = CalibrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
