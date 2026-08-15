<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionTargetResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionTargetResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListProductionTargets extends ListRecords
{
    protected static string $resource = ProductionTargetResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
