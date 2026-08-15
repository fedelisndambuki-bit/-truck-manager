<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeExceptionResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeExceptionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWeighbridgeExceptions extends ListRecords
{
    protected static string $resource = WeighbridgeExceptionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
