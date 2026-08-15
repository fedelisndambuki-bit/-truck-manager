<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources\TestMethodResource\Pages;

use App\Filament\Clusters\LaboratoryQualityControl\Resources\TestMethodResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTestMethods extends ListRecords
{
    protected static string $resource = TestMethodResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
