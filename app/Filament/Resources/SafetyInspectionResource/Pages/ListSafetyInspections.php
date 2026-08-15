<?php

namespace App\Filament\Resources\SafetyInspectionResource\Pages;

use App\Filament\Resources\SafetyInspectionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSafetyInspections extends ListRecords
{
    protected static string $resource = SafetyInspectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
