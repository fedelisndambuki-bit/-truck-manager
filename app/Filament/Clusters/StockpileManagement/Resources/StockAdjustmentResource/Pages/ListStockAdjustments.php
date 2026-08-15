<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
