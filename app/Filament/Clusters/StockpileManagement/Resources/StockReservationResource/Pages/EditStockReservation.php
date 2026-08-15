<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockReservationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockReservation extends EditRecord
{
    protected static string $resource = StockReservationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
