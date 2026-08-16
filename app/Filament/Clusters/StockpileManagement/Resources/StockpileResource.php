<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\StockpileResource\Pages;
use App\Models\PlantQualityTest;
use App\Models\ProductionStockTransfer;
use App\Models\StockMovement;
use App\Models\Stockpile;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\Tabs;
use Filament\Infolists\Components\Tabs\Tab;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockpileResource extends Resource
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

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Stockpiles';

    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['material', 'extractionSite']);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(Stockpile::STATUSES, Stockpile::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Active' => 'success',
            'Inactive' => 'gray',
            'Maintenance' => 'warning',
            'Closed' => 'danger',
            default => 'gray',
        };
    }

    public static function getQualityStatusColor(string $state): string
    {
        return match ($state) {
            'Approved' => 'success',
            'Pending Test' => 'warning',
            'On Hold' => 'warning',
            'Rejected', 'Expired' => 'danger',
            default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Stockpile Details')->schema([
                    Forms\Components\TextInput::make('stockpile_code')->label('Stockpile Code')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('label')->label('Stockpile Name')->placeholder('e.g. Stockyard A — Bay 3'),
                    Forms\Components\Select::make('extraction_site_id')
                        ->label('Quarry')
                        ->relationship('extractionSite', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('crushing_plant_id')
                        ->label('Crushing Plant')
                        ->relationship('crushingPlant', 'name')
                        ->searchable()->preload()
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->name ?? ('Plant #' . $record->id)),
                    Forms\Components\TextInput::make('stockyard')->label('Stockyard / Depot Name'),
                    Forms\Components\Select::make('location_type')->options(array_combine(Stockpile::LOCATION_TYPES, Stockpile::LOCATION_TYPES))->default('Stockyard')->required(),
                    Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric(),
                    Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric(),
                    Forms\Components\Select::make('material_id')
                        ->label('Material Type')
                        ->relationship('material', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('max_level')->label('Maximum Capacity')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('quantity_available')->label('Current Quantity')->numeric()->suffix('t')->default(0)->required(),
                    Forms\Components\TextInput::make('quantity_reserved')->label('Reserved Quantity')->numeric()->suffix('t')->default(0),
                    Forms\Components\TextInput::make('min_level')->label('Minimum Level')->numeric()->suffix('t')->default(0),
                    Forms\Components\TextInput::make('unit_value')->label('Value per Tonne')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Active')->required(),
                    Forms\Components\Select::make('quality_status')->options(array_combine(Stockpile::QUALITY_STATUSES, Stockpile::QUALITY_STATUSES))->default('Approved')->required(),
                ])->columns(3),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Tabs::make('Stockpile')->tabs([
                Tab::make('Overview')->schema([
                    Section::make()->schema([
                        TextEntry::make('stockpile_code')->label('Code'),
                        TextEntry::make('label')->label('Name')->placeholder('—'),
                        TextEntry::make('material.name')->label('Material'),
                        TextEntry::make('extractionSite.name')->label('Quarry'),
                        TextEntry::make('crushingPlant.name')->label('Crushing Plant')->placeholder('—'),
                        TextEntry::make('stockyard')->label('Stockyard / Depot')->placeholder('—'),
                        TextEntry::make('location_type')->label('Location Type'),
                        TextEntry::make('gps_lat')->label('GPS Latitude')->placeholder('—'),
                        TextEntry::make('gps_lng')->label('GPS Longitude')->placeholder('—'),
                        TextEntry::make('max_level')->label('Maximum Capacity')->suffix(' t')->placeholder('—'),
                        TextEntry::make('quantity_available')->label('Current Quantity')->suffix(' t'),
                        TextEntry::make('available_capacity')->label('Available Capacity')
                            ->state(fn (Stockpile $record) => $record->availableCapacity() !== null ? $record->availableCapacity() . ' t' : '—'),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                        TextEntry::make('quality_status')->badge()->color(fn (string $state): string => self::getQualityStatusColor($state)),
                    ])->columns(4),
                ]),

                Tab::make('Inventory')->schema([
                    Section::make()->schema([
                        TextEntry::make('quantity_available')->label('Current Quantity')->suffix(' t'),
                        TextEntry::make('quantity_reserved')->label('Reserved Quantity')->suffix(' t'),
                        TextEntry::make('available_for_sale')->label('Available Quantity')
                            ->state(fn (Stockpile $record) => $record->availableForSale() . ' t'),
                        TextEntry::make('current_value')->label('Current Value')
                            ->state(fn (Stockpile $record) => session('currency', 'KES') . ' ' . number_format($record->value(), 2)),
                        TextEntry::make('last_movement')->label('Last Movement')
                            ->state(function (Stockpile $record) {
                                $last = StockMovement::query()
                                    ->where('source_stockpile_id', $record->id)
                                    ->orWhere('destination_stockpile_id', $record->id)
                                    ->latest('date')->first();

                                return $last ? $last->movement_type . ' — ' . $last->date->format('d M Y') : '—';
                            }),
                    ])->columns(3),
                ]),

                Tab::make('Movements')->schema([
                    RepeatableEntry::make('movementHistory')
                        ->label('Recent Movements')
                        ->getStateUsing(fn (Stockpile $record) => StockMovement::query()
                            ->where('source_stockpile_id', $record->id)
                            ->orWhere('destination_stockpile_id', $record->id)
                            ->latest('date')->limit(15)->get())
                        ->schema([
                            TextEntry::make('movement_number')->label('Movement #'),
                            TextEntry::make('movement_type')->badge(),
                            TextEntry::make('date')->date(),
                            TextEntry::make('quantity')->suffix(' t'),
                        ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Reservations')->schema([
                    RepeatableEntry::make('reservations')->label('Reservations')->schema([
                        TextEntry::make('reservation_number')->label('Reservation #'),
                        TextEntry::make('customer.company_name')->label('Customer')->placeholder('—'),
                        TextEntry::make('quantity_reserved')->suffix(' t'),
                        TextEntry::make('status')->badge(),
                    ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Transfers')->schema([
                    RepeatableEntry::make('relatedTransfers')
                        ->label('Related Stock Transfers')
                        ->getStateUsing(fn (Stockpile $record) => ProductionStockTransfer::query()
                            ->where('source_stockpile_id', $record->id)
                            ->orWhere('destination_stockpile_id', $record->id)
                            ->latest('created_at')->limit(15)->get())
                        ->schema([
                            TextEntry::make('transfer_number')->label('Transfer #'),
                            TextEntry::make('quantity')->suffix(' t'),
                            TextEntry::make('status')->badge(),
                            TextEntry::make('received_status')->label('Received'),
                        ])->columns(4)->columnSpanFull(),
                ]),

                Tab::make('Quality')->schema([
                    Section::make()->schema([
                        TextEntry::make('quality_status')->badge()->color(fn (string $state): string => self::getQualityStatusColor($state)),
                    ]),
                    RepeatableEntry::make('materialQualityTests')
                        ->label('Related Lab Tests (Material)')
                        ->getStateUsing(fn (Stockpile $record) => PlantQualityTest::query()
                            ->where('material_id', $record->material_id)
                            ->latest('created_at')->limit(10)->get())
                        ->schema([
                            TextEntry::make('sample_number'),
                            TextEntry::make('pass_fail')->label('Result')->formatStateUsing(fn ($state) => $state ? 'Pass' : 'Fail')->badge()
                                ->color(fn ($state) => $state ? 'success' : 'danger'),
                            TextEntry::make('approved_for_dispatch')->label('Approved for Dispatch')->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        ])->columns(3)->columnSpanFull(),
                ]),

                Tab::make('Documents')->schema([
                    RepeatableEntry::make('documents')->hiddenLabel()->schema([
                        TextEntry::make('category')->badge(),
                        TextEntry::make('file_path')->label('File')->formatStateUsing(fn ($state) => $state ? basename($state) : '—'),
                    ])->columns(2)->columnSpanFull(),
                ]),

                Tab::make('Analytics')->schema([
                    Section::make()->schema([
                        TextEntry::make('turnover_contribution')
                            ->label('Dispatched (30 Days)')
                            ->state(function (Stockpile $record) {
                                $dispatched = StockMovement::query()
                                    ->where('source_stockpile_id', $record->id)
                                    ->where('movement_type', 'Dispatch')
                                    ->where('date', '>=', now()->subDays(30))
                                    ->sum('quantity');

                                return number_format((float) $dispatched, 1) . ' t';
                            }),
                        TextEntry::make('utilization_percent')
                            ->label('Capacity Utilization')
                            ->state(function (Stockpile $record) {
                                if (! $record->max_level) {
                                    return '—';
                                }

                                return round(((float) $record->quantity_available / (float) $record->max_level) * 100, 1) . '%';
                            }),
                        TextEntry::make('reservation_ratio')
                            ->label('Reserved / Available Ratio')
                            ->state(function (Stockpile $record) {
                                if ((float) $record->quantity_available <= 0) {
                                    return '—';
                                }

                                return round(((float) $record->quantity_reserved / (float) $record->quantity_available) * 100, 1) . '%';
                            }),
                    ])->columns(3),
                ]),

                Tab::make('History')->schema([
                    Section::make()->schema([
                        TextEntry::make('created_at')->dateTime(),
                        TextEntry::make('updated_at')->label('Last Updated')->dateTime(),
                        TextEntry::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                    ])->columns(3),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('stockpile_code')->label('Code')->searchable()->sortable()->placeholder('—'),
                Tables\Columns\TextColumn::make('label')->label('Stockpile Name')->searchable()->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Material')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('extractionSite.name')->label('Quarry'),
                Tables\Columns\TextColumn::make('stockyard')->label('Stockyard')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('location_type')->label('Type')->toggleable(),
                Tables\Columns\TextColumn::make('quantity_available')
                    ->label('Current Qty')
                    ->numeric()
                    ->suffix(' t')
                    ->color(fn (Stockpile $record): string => $record->isLow() ? 'danger' : 'success')
                    ->weight(fn (Stockpile $record): string => $record->isLow() ? 'bold' : 'normal'),
                Tables\Columns\TextColumn::make('quantity_reserved')->label('Reserved')->numeric()->suffix(' t')->toggleable(),
                Tables\Columns\TextColumn::make('max_level')->label('Max Capacity')->numeric()->suffix(' t')->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('quality_status')->label('Quality')->badge()->color(fn (string $state): string => self::getQualityStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('quality_status')->label('Quality Status')->options(array_combine(Stockpile::QUALITY_STATUSES, Stockpile::QUALITY_STATUSES)),
                Tables\Filters\SelectFilter::make('extraction_site_id')->label('Quarry')->relationship('extractionSite', 'name'),
                Tables\Filters\SelectFilter::make('material_id')->label('Material')->relationship('material', 'name'),
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock Only')
                    ->query(fn (Builder $query) => $query->whereColumn('quantity_available', '<=', 'min_level')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockpiles::route('/'),
            'create' => Pages\CreateStockpile::route('/create'),
            'edit' => Pages\EditStockpile::route('/{record}/edit'),
        ];
    }
}
