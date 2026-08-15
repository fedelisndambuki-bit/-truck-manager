<?php

namespace App\Filament\Clusters\BlastManagement\Resources\BlastDrillHoleResource\Pages;

use App\Filament\Clusters\BlastManagement\Resources\BlastDrillHoleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBlastDrillHoles extends ListRecords
{
    protected static string $resource = BlastDrillHoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
