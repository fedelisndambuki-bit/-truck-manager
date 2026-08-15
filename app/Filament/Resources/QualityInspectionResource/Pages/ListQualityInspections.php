<?php

namespace App\Filament\Resources\QualityInspectionResource\Pages;

use App\Filament\Resources\QualityInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQualityInspections extends ListRecords
{
    protected static string $resource = QualityInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
