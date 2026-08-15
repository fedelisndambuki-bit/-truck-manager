<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BinLocationResource\Pages;
use App\Models\BinLocation;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BinLocationResource extends Resource
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

    protected static ?string $model = BinLocation::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    protected static ?string $navigationLabel = 'Bin Locations';

    protected static ?string $navigationGroup = 'Materials Management';

    protected static ?int $navigationSort = 6;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Bin Details')->schema([
                    Forms\Components\Select::make('warehouse_id')
                        ->relationship('warehouse', 'name')
                        ->searchable()->preload()->required()->live(),
                    Forms\Components\Select::make('zone_id')
                        ->label('Zone')
                        ->relationship('zone', 'name', fn (Forms\Get $get, $query) => $get('warehouse_id')
                            ? $query->where('warehouse_id', $get('warehouse_id'))
                            : $query)
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('code')->required()->unique(ignoreRecord: true)
                        ->helperText('e.g. A-01-02-03'),
                    Forms\Components\TextInput::make('aisle'),
                    Forms\Components\TextInput::make('rack'),
                    Forms\Components\TextInput::make('shelf'),
                    Forms\Components\TextInput::make('bin'),
                    Forms\Components\TextInput::make('barcode'),
                    Forms\Components\TextInput::make('capacity')->numeric(),
                    Forms\Components\Select::make('status')
                        ->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Full' => 'Full', 'Blocked' => 'Blocked'])
                        ->default('Active')->required(),
                    Forms\Components\Placeholder::make('qr_code_note')
                        ->label('QR Code')
                        ->content('A scannable QR code encoding the bin code is generated automatically once this bin is saved.'),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('warehouse.name')->label('Warehouse')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('zone.name')->label('Zone')->toggleable(),
                Tables\Columns\TextColumn::make('capacity')->placeholder('—')->toggleable(),
                Tables\Columns\TextColumn::make('total_stock')->label('Current Stock')->state(fn (BinLocation $record) => $record->total_stock),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state) => match ($state) {
                    'Active' => 'success', 'Full' => 'warning', 'Blocked' => 'danger', default => 'gray',
                }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('warehouse_id')->relationship('warehouse', 'name'),
                Tables\Filters\SelectFilter::make('status')->options(['Active' => 'Active', 'Inactive' => 'Inactive', 'Full' => 'Full', 'Blocked' => 'Blocked']),
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
            'index' => Pages\ListBinLocations::route('/'),
            'create' => Pages\CreateBinLocation::route('/create'),
            'edit' => Pages\EditBinLocation::route('/{record}/edit'),
        ];
    }
}
