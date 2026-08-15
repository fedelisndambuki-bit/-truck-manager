<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityStandardResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQualityStandard extends ViewRecord
{
    protected static string $resource = QualityStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
