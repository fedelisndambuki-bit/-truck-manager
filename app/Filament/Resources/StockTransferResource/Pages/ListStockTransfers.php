<?php

namespace App\Filament\Resources\StockTransferResource\Pages;

use App\Filament\Resources\StockTransferResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockTransfers extends ListRecords
{
    protected static string $resource = StockTransferResource::class;

    protected static string $view = 'filament.resources.stock-transfer-resource.pages.list-stock-transfers';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Transfer'),
        ];
    }
}
