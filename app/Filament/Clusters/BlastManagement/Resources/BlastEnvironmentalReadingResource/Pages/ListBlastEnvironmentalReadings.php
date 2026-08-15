<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastEnvironmentalReadingResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastEnvironmentalReadingResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlastEnvironmentalReadings extends ListRecords
{
    protected static string $resource = BlastEnvironmentalReadingResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
