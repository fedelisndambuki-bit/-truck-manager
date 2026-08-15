<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\SamplingRequestResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\SamplingRequestResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSamplingRequest extends EditRecord
{
    protected static string $resource = SamplingRequestResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
