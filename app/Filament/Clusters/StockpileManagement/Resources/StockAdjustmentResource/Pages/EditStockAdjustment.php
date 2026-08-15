<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditStockAdjustment extends EditRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['supporting_document']);

        return $data;
    }
}
