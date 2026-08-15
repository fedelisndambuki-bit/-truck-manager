<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeResource\Pages;

use App\Filament\Clusters\SmartWeighbridge\Resources\WeighbridgeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWeighbridge extends EditRecord
{
    protected static string $resource = WeighbridgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
