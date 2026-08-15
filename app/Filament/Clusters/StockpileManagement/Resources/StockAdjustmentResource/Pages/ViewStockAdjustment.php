<?php

namespace App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource\Pages;

use App\Filament\Clusters\StockpileManagement\Resources\StockAdjustmentResource;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewStockAdjustment extends ViewRecord
{
    protected static string $resource = StockAdjustmentResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Adjustment Details')->schema([
                TextEntry::make('adjustment_number')->label('Adjustment #'),
                TextEntry::make('stockpile.label')->label('Stockpile')->placeholder('—'),
                TextEntry::make('quantity')->suffix(' t'),
                TextEntry::make('reason')->badge(),
                TextEntry::make('requestedBy.full_name')->label('Requested By')->placeholder('—'),
                TextEntry::make('status')->badge()->color(fn (string $state): string => StockAdjustmentResource::getStatusColor($state)),
            ])->columns(3),
            Section::make('Supporting Documents')->schema([
                RepeatableEntry::make('documents')->hiddenLabel()->schema([
                    TextEntry::make('category')->badge(),
                    TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                ])->columns(2)->columnSpanFull(),
            ]),
        ]);
    }
}
