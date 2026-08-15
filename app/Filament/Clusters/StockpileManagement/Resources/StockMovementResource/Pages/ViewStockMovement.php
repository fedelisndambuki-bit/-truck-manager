<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockMovementResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockMovementResource;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockMovement extends ViewRecord
{
    protected static string $resource = StockMovementResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Movement Details')->schema([
                TextEntry::make('movement_number')->label('Movement #'),
                TextEntry::make('movement_type')->badge()->color(fn (string $state): string => StockMovementResource::getMovementTypeColor($state)),
                TextEntry::make('date')->date(),
                TextEntry::make('material.name')->label('Product')->placeholder('—'),
                TextEntry::make('quantity')->suffix(' t'),
                TextEntry::make('sourceStockpile.label')->label('Source')->placeholder('—'),
                TextEntry::make('destinationStockpile.label')->label('Destination')->placeholder('—'),
                TextEntry::make('operator.full_name')->label('Operator')->placeholder('—'),
                TextEntry::make('vehicle_registration')->label('Vehicle')->placeholder('—'),
                TextEntry::make('equipment.name')->label('Equipment')->placeholder('—'),
                TextEntry::make('reference_type')
                    ->label('Reference Module')
                    ->state(fn ($record) => $record->reference_type ? class_basename($record->reference_type) . ' #' . $record->reference_id : 'Manual Entry'),
            ])->columns(3),
        ]);
    }
}
