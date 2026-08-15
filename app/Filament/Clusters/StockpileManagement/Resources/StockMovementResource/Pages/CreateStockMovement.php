<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockMovementResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockMovementResource;
use App\Models\StockMovement;
use Filament\Resources\Pages\CreateRecord;

class CreateStockMovement extends CreateRecord
{
    protected static string $resource = StockMovementResource::class;

    protected function afterCreate(): void
    {
        /** @var StockMovement $movement */
        $movement = $this->record;

        match ($movement->movement_type) {
            'Waste', 'Internal Consumption' => $movement->sourceStockpile?->decrement('quantity_available', $movement->quantity),
            'Customer Return' => $movement->destinationStockpile?->increment('quantity_available', $movement->quantity),
            default => null,
        };
    }
}
