<?php

namespace App\Filament\Clusters\StockpileManagement\Resources;

use App\Filament\Clusters\StockpileManagement;
use App\Filament\Clusters\StockpileManagement\Resources\StockpileResource\Pages;
use App\Models\Stockpile;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
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
            'view' => Pages\ViewStockpile::route('/{record}'),
            'edit' => Pages\EditStockpile::route('/{record}/edit'),
        ];
    }
}
