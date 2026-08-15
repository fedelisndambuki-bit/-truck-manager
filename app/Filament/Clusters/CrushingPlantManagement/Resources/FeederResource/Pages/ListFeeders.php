<?php

namespace App\Filament\Clusters\CrushingPlantManagement\Resources\FeederResource\Pages;

use App\Filament\Clusters\CrushingPlantManagement\Resources\FeederResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeeders extends ListRecords
{
    protected static string $resource = FeederResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
