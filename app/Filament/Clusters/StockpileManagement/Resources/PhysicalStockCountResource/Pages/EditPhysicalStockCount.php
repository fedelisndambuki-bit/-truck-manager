<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditPhysicalStockCount extends EditRecord
{
    protected static string $resource = PhysicalStockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
