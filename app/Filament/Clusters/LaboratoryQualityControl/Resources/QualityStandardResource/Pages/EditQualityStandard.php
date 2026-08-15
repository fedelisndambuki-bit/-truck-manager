<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityStandardResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityStandardResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQualityStandard extends EditRecord
{
    protected static string $resource = QualityStandardResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
