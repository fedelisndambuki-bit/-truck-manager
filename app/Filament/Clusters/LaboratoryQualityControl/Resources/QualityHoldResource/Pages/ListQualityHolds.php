<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\QualityHoldResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQualityHolds extends ListRecords
{
    protected static string $resource = QualityHoldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
