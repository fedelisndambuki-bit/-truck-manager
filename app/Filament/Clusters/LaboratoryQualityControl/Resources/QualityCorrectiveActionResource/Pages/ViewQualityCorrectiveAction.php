<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityCorrectiveActionResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityCorrectiveActionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewQualityCorrectiveAction extends ViewRecord
{
    protected static string $resource = QualityCorrectiveActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
