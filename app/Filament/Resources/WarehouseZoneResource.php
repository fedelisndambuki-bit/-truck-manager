<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseZoneResource\Pages;
use App\Models\WarehouseZone;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WarehouseZoneResource extends Resource
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

    protected static ?string $model = WarehouseZone::class;

    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';

    protected static ?string $navigationLabel = 'Warehouse Zones';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 5;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['warehouse']);
    }

    public static function getTypeOptions(): array
    {
        return array_combine(
            $types = ['Receiving', 'Inspection', 'Storage', 'Bulk', 'Picking', 'Packing', 'Dispatch', 'Returns', 'Quarantine', 'Damaged'],
            $types
        );
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Zone Details')->schema([
                    Forms\Components\Select::make('warehouse_id')
                        ->relationship('warehouse', 'name')
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\Select::make('type')->options(self::getTypeOptions())->required(),
                    Forms\Components\Select::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive'])->default('Active')->required(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('binLocations_count')->label('Bins')->counts('binLocations'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('type')->options(self::getTypeOptions()),
            ])
            ->actions([
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
            'index' => Pages\ListWarehouseZones::route('/'),
            'create' => Pages\CreateWarehouseZone::route('/create'),
            'edit' => Pages\EditWarehouseZone::route('/{record}/edit'),
        ];
    }
}
