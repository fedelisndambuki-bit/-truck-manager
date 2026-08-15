<?php

namespace App\Filament\Resources\MedicalCaseResource\Pages;

use App\Filament\Resources\MedicalCaseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListMedicalCases extends ListRecords
{
    protected static string $resource = MedicalCaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
