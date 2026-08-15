<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabTestResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabTestResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewLabTest extends ViewRecord
{
    protected static string $resource = LabTestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
