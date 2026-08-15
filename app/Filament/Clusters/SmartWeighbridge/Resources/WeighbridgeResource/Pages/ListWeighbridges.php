<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeighbridges extends ListRecords
{
    protected static string $resource = WeighbridgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
