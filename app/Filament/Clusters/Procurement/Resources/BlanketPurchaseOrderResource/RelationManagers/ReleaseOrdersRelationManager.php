<?php

namespace App\Filament\Clusters\Procurement\Resources\BlanketPurchaseOrderResource\RelationManagers;

use App\Filament\Clusters\Procurement\Resources\PurchaseOrderResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ReleaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'releaseOrders';

    protected static ?string $title = 'Release Orders';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('po_number')
            ->columns([
                Tables\Columns\TextColumn::make('po_number')->label('PO #'),
                Tables\Columns\TextColumn::make('total_amount')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => PurchaseOrderResource::getStatusColor($state)),
                Tables\Columns\TextColumn::make('created_at')->dateTime(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn ($record) => PurchaseOrderResource::getUrl('edit', ['record' => $record])),
            ]);
    }
}
