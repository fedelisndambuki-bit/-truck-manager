<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabSampleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLabSamples extends ListRecords
{
    protected static string $resource = LabSampleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
