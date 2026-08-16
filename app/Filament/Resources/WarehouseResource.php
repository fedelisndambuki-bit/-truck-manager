<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WarehouseResource\Pages;
use App\Models\Warehouse;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class WarehouseResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['construction'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'materials.view';
    }

    protected static ?string $model = Warehouse::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationLabel = 'Warehouses';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 4;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['manager']);
    }

    public static function getTypeOptions(): array
    {
        return [
            'Central' => 'Central Warehouse',
            'Project Warehouse' => 'Project Warehouse',
            'Site Store' => 'Site Store',
            'Temporary Storage' => 'Temporary Storage',
            'Mobile Store' => 'Mobile Store',
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Warehouse Details')->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('code'),
                    Forms\Components\Select::make('type')->options(self::getTypeOptions())->default('Central')->required()->reactive(),
                    Forms\Components\TextInput::make('location'),
                    Forms\Components\Select::make('manager_id')
                        ->label('Manager')
                        ->relationship('manager', 'full_name')
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('capacity')->numeric()->suffix('units'),
                    Forms\Components\Select::make('project_id')
                        ->label('Project')
                        ->relationship('project', 'name')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'Project Warehouse'),
                    Forms\Components\Select::make('site_id')
                        ->label('Site')
                        ->relationship('site', 'name')
                        ->searchable()->preload()
                        ->visible(fn (Forms\Get $get) => $get('type') === 'Site Store'),
                    Forms\Components\Select::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive'])->default('Active')->required(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('location')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('manager.full_name')->label('Manager')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('capacity')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('current_stock_value')->label('Stock Value')->money(session('currency', 'KES'))->state(fn (Warehouse $record) => $record->current_stock_value),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(self::getTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive']),
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
            'index' => Pages\ListWarehouses::route('/'),
            'create' => Pages\CreateWarehouse::route('/create'),
            'edit' => Pages\EditWarehouse::route('/{record}/edit'),
        ];
    }
}
