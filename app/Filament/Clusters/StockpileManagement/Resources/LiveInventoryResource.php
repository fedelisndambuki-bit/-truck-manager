<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\LiveInventoryResource\Pages;
use App\Models\ProductionOutput;
use App\Models\StockMovement;
use App\Models\Stockpile;
use App\Traits\HasModuleAccess;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * A read-only, real-time view over the same Stockpile table — computed
 * columns (available, last movement, average production date, value) rather
 * than a duplicate inventory ledger.
 */
class LiveInventoryResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = StockpileManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'stockpile.view';
    }

    protected static ?string $model = Stockpile::class;

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Inventory';

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'inventory';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('material.name')->label('Product')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('label')->label('Stockpile')->placeholder('—'),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('quantity_available')->label('Current Quantity')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('quantity_reserved')->label('Reserved Quantity')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('available_quantity')
                    ->label('Available Quantity')
                    ->state(fn (Stockpile $record) => number_format($record->availableForSale(), 2) . ' t'),
                Tables\Columns\TextColumn::make('quality_status')->badge()
                    ->color(fn (string $state): string => StockpileResource::getQualityStatusColor($state)),
                Tables\Columns\TextColumn::make('average_production_date')
                    ->label('Avg. Production Date')
                    ->state(function (Stockpile $record) {
                        $timestamps = ProductionOutput::query()
                            ->where('stockpile_id', $record->id)
                            ->get(['created_at'])
                            ->pluck('created_at')
                            ->filter();

                        if ($timestamps->isEmpty()) {
                            return '—';
                        }

                        $avgTimestamp = (int) $timestamps->avg(fn ($date) => $date->timestamp);

                        return \Illuminate\Support\Carbon::createFromTimestamp($avgTimestamp)->format('d M Y');
                    }),
                Tables\Columns\TextColumn::make('last_movement')
                    ->label('Last Movement')
                    ->state(function (Stockpile $record) {
                        $last = StockMovement::query()
                            ->where('source_stockpile_id', $record->id)
                            ->orWhere('destination_stockpile_id', $record->id)
                            ->latest('date')
                            ->first();

                        return $last ? $last->movement_type . ' — ' . $last->date->format('d M Y') : '—';
                    }),
                Tables\Columns\TextColumn::make('current_value')
                    ->label('Current Value')
                    ->state(fn (Stockpile $record) => session('currency', 'KES') . ' ' . number_format($record->value(), 2)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('material_id')->label('Product')->relationship('material', 'name'),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
                Tables\Filters\SelectFilter::make('quality_status')->options(array_combine(Stockpile::QUALITY_STATUSES, Stockpile::QUALITY_STATUSES)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->infolist(fn (Infolist $infolist) => StockpileResource::infolist($infolist)),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLiveInventory::route('/'),
        ];
    }
}
