<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\QuarryFaceResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\QuarryFaceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQuarryFaces extends ListRecords
{
    protected static string $resource = QuarryFaceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
