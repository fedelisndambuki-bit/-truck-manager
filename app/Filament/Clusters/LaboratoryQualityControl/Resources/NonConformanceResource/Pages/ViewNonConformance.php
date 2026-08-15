<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\NonConformanceResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\NonConformanceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNonConformance extends ViewRecord
{
    protected static string $resource = NonConformanceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
