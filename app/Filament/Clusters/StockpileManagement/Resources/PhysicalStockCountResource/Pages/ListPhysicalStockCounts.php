<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\PhysicalStockCountResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPhysicalStockCounts extends ListRecords
{
    protected static string $resource = PhysicalStockCountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
