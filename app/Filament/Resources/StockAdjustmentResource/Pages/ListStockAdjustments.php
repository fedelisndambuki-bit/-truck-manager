<?php

namespace App\Filament\Resources\StockAdjustmentResource\Pages;

use App\Filament\Resources\StockAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStockAdjustments extends ListRecords
{
    protected static string $resource = StockAdjustmentResource::class;

    protected static string $view = 'filament.resources.stock-adjustment-resource.pages.list-stock-adjustments';

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New Adjustment'),
        ];
    }
}
