<?php

namespace App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource\Pages;

use App\Filament\Clusters\ProductionManagement\Resources\ProductionRecordResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditProductionRecord extends EditRecord
{
    protected static string $resource = ProductionRecordResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
