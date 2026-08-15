<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\TestMethodResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\TestMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewTestMethod extends ViewRecord
{
    protected static string $resource = TestMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
