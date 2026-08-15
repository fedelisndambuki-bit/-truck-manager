<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockpileResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockpileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockpiles extends ListRecords
{
    protected static string $resource = StockpileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
