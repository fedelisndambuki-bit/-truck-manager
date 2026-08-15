<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The automated stock ledger. Most rows are system-generated (Production
 * Receipt, Transfer, Dispatch, Adjustment, Quality Hold/Release) by the
 * models that actually move stock; this Resource also supports manual entry
 * for the handful of movement types with no other originating record
 * (Waste, Internal Consumption, Customer Return).
 */
class StockMovementResource extends Resource
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

    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';

    protected static ?string $navigationLabel = 'Stock Movements';

    protected static ?int $navigationSort = 5;

    public const MANUAL_MOVEMENT_TYPES = ['Waste', 'Internal Consumption', 'Customer Return'];

    public static function getMovementTypeColor(string $state): string
    {
        return match ($state) {
            'Production Receipt' => 'success',
            'Transfer' => 'info',
            'Dispatch' => 'primary',
            'Customer Return' => 'info',
            'Adjustment' => 'warning',
            'Waste' => 'danger',
            'Quality Hold' => 'danger',
            'Quality Release' => 'success',
            'Internal Consumption' => 'gray',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Manual Stock Movement')
                    ->description('For movement types not otherwise recorded automatically by Transfers, Adjustments, Physical Counts or Weighbridge dispatch.')
                    ->schema([
                        Forms\Components\Select::make('movement_type')
                            ->options(array_combine(self::MANUAL_MOVEMENT_TYPES, self::MANUAL_MOVEMENT_TYPES))
                            ->required()
                            ->live(),
                        Forms\Components\DatePicker::make('date')->default(now())->required(),
                        Forms\Components\Select::make('material_id')->label('Product')->relationship('material', 'name')->searchable()->preload()->required(),
                        Forms\Components\TextInput::make('quantity')->numeric()->suffix('t')->required(),
                        Forms\Components\Select::make('source_stockpile_id')
                            ->label('Source Stockpile')
                            ->relationship('sourceStockpile', 'label')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                            ->searchable()->preload()
                            ->visible(fn (Forms\Get $get) => in_array($get('movement_type'), ['Waste', 'Internal Consumption'])),
                        Forms\Components\Select::make('destination_stockpile_id')
                            ->label('Destination Stockpile')
                            ->relationship('destinationStockpile', 'label')
                            ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                            ->searchable()->preload()
                            ->visible(fn (Forms\Get $get) => $get('movement_type') === 'Customer Return'),
                        Forms\Components\TextInput::make('vehicle_registration')->label('Vehicle'),
                        Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    ])->columns(3),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make('Movement Details')->schema([
                TextEntry::make('movement_number')->label('Movement #'),
                TextEntry::make('movement_type')->badge()->color(fn (string $state): string => self::getMovementTypeColor($state)),
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

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('movement_number')->label('Movement #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('date')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('movement_type')->badge()->color(fn (string $state): string => self::getMovementTypeColor($state)),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity')->numeric()->suffix(' t'),
                Tables\Columns\TextColumn::make('sourceStockpile.label')->label('Source')->placeholder('—'),
                Tables\Columns\TextColumn::make('destinationStockpile.label')->label('Destination')->placeholder('—'),
                Tables\Columns\TextColumn::make('operator.full_name')->label('Operator')->placeholder('—'),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('reference_type')
                    ->label('Reference Module')
                    ->state(fn (StockMovement $record) => $record->reference_type ? class_basename($record->reference_type) . ' #' . $record->reference_id : 'Manual Entry')
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('movement_type')->options(array_combine(StockMovement::MOVEMENT_TYPES, StockMovement::MOVEMENT_TYPES)),
                Tables\Filters\SelectFilter::make('material_id')->label('Product')->relationship('material', 'name'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
            'create' => Pages\CreateStockMovement::route('/create'),
        ];
    }
}
