<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLabSample extends EditRecord
{
    protected static string $resource = LabSampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
