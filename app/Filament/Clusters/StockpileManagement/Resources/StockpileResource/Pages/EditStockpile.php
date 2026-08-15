<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockpileResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockpileResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockpile extends EditRecord
{
    protected static string $resource = StockpileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
