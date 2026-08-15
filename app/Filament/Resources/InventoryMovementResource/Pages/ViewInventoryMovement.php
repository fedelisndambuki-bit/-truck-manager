<?php

namespace App\Filament\Resources\InventoryMovementResource\Pages;

use App\Filament\Resources\InventoryMovementResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewInventoryMovement extends ViewRecord
{
    protected static string $resource = InventoryMovementResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            TextEntry::make('movement_number')->label('Movement #'),
            TextEntry::make('date')->date(),
            TextEntry::make('movement_type')->badge(),
            TextEntry::make('material.name')->label('Material'),
            TextEntry::make('warehouse.name')->label('Warehouse'),
            TextEntry::make('sourceBin.code')->label('Source Bin')->placeholder('—'),
            TextEntry::make('destinationBin.code')->label('Destination Bin')->placeholder('—'),
            TextEntry::make('quantity')->numeric(2),
            TextEntry::make('operator.full_name')->label('Operator')->placeholder('—'),
            TextEntry::make('description')->placeholder('—')->columnSpanFull(),
        ])->columns(2);
    }
}
