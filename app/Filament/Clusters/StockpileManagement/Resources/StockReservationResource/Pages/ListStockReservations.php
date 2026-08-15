<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockReservations extends ListRecords
{
    protected static string $resource = StockReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
