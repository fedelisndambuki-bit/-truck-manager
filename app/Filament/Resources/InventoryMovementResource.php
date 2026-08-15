<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use App\Traits\HasModuleAccess;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The unified general-materials stock ledger — every row is system-generated
 * by GoodsReceivedNote::approve(), StockTransfer::approve(),
 * StockAdjustment::approve(), MaterialConsumption's created hook,
 * MaterialReturn::approve(), or PutAwayTask::complete(). Read-only, mirroring
 * the equivalent quarry Stockpile ledger (StockMovementResource).
 */
class InventoryMovementResource extends Resource
{
    use HasModuleAccess;

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    protected static ?string $model = InventoryMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-queue-list';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 13;

    public static function getMovementTypeColor(string $state): string
    {
        return match ($state) {
            'Receipt' => 'success',
            'Transfer', 'PutAway' => 'info',
            'Return' => 'primary',
            'Adjustment' => 'warning',
            'Issue', 'Consumption' => 'gray',
            default => 'gray',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('movement_number')->label('Movement #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('movement_type')->badge()->color(fn (string $state) => self::getMovementTypeColor($state)),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->placeholder('—'),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse')->placeholder('—'),
                Tables\Columns\TextColumn::make('sourceBin.code')->label('Source Bin')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('destinationBin.code')->label('Destination Bin')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('quantity')->numeric(2),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Reference')
                    ->state(fn (InventoryMovement $record) => $record->reference_type ? class_basename($record->reference_type) . ' #' . $record->reference_id : 'Manual Entry')
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')->options(array_combine(InventoryMovement::MOVEMENT_TYPES, InventoryMovement::MOVEMENT_TYPES)),
                Tables\Filters\SelectFilter::make('material_id')->relationship('material', 'name'),
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
            'view' => Pages\ViewInventoryMovement::route('/{record}'),
        ];
    }
}
