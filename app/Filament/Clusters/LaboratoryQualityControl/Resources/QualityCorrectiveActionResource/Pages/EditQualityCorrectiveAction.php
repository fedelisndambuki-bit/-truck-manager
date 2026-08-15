<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityCorrectiveActionResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityCorrectiveActionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQualityCorrectiveAction extends EditRecord
{
    protected static string $resource = QualityCorrectiveActionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
