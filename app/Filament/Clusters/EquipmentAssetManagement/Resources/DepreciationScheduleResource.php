<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource\Pages;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\DepreciationScheduleResource\RelationManagers;
use App\Models\DepreciationSchedule;
use App\Models\Equipment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepreciationScheduleResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = EquipmentAssetManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'eam.view';
    }

    protected static ?string $model = DepreciationSchedule::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-trending-down';

    protected static ?string $navigationLabel = 'Depreciation';

    protected static ?int $navigationSort = 21;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['equipment']);
    }

    public static function getMethodOptions(): array
    {
        return array_combine(DepreciationSchedule::METHODS, DepreciationSchedule::METHODS);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Schedule')->schema([
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('method')->options(self::getMethodOptions())->required()->live(),
                    Forms\Components\DatePicker::make('start_date')->default(now())->required(),
                    Forms\Components\Select::make('status')->options(array_combine(DepreciationSchedule::STATUSES, DepreciationSchedule::STATUSES))->default('Active')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Basis')->schema([
                    Forms\Components\TextInput::make('cost_basis')->numeric()->prefix(session('currency', 'KES'))->required(),
                    Forms\Components\TextInput::make('residual_value')->numeric()->prefix(session('currency', 'KES'))->default(0),
                    Forms\Components\TextInput::make('useful_life_years')->numeric()
                        ->visible(fn (Forms\Get $get) => $get('method') === 'StraightLine'),
                    Forms\Components\TextInput::make('rate_percent')->numeric()->suffix('% / yr')
                        ->visible(fn (Forms\Get $get) => $get('method') === 'ReducingBalance'),
                    Forms\Components\Select::make('units_basis')->options(['engine_hours' => 'Engine Hours'])
                        ->visible(fn (Forms\Get $get) => $get('method') === 'UnitsOfProduction'),
                    Forms\Components\TextInput::make('total_expected_units')->numeric()
                        ->visible(fn (Forms\Get $get) => $get('method') === 'UnitsOfProduction'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('method')->badge(),
                Tables\Columns\TextColumn::make('cost_basis')->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('book_value')->label('Book Value')->state(fn (DepreciationSchedule $record) => $record->currentBookValue())->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('accumulated')->label('Accumulated')->state(fn (DepreciationSchedule $record) => $record->accumulatedDepreciation())->money(session('currency', 'KES')),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('method')->options(self::getMethodOptions()),
                Tables\Filters\SelectFilter::make('status')->options(array_combine(DepreciationSchedule::STATUSES, DepreciationSchedule::STATUSES)),
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

    public static function getRelations(): array
    {
        return [
            RelationManagers\EntriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepreciationSchedules::route('/'),
            'create' => Pages\CreateDepreciationSchedule::route('/create'),
            'edit' => Pages\EditDepreciationSchedule::route('/{record}/edit'),
        ];
    }
}
