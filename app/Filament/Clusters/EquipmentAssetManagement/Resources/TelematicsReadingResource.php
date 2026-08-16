<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\TelematicsReadingResource\Pages;
use App\Models\Equipment;
use App\Models\TelematicsReading;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TelematicsReadingResource extends Resource
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

    protected static ?string $model = TelematicsReading::class;

    protected static ?string $navigationIcon = 'heroicon-o-signal';

    protected static ?string $navigationLabel = 'Telematics';

    protected static ?int $navigationSort = 19;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['equipment']);
    }

    public static function getSourceOptions(): array
    {
        return array_combine(TelematicsReading::SOURCES, TelematicsReading::SOURCES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reading')->schema([
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('source')->label('Telematics Provider')->options(self::getSourceOptions())->default('Manual')->required(),
                    Forms\Components\DateTimePicker::make('recorded_at')->default(now())->required(),
                ])->columns(3),

                Forms\Components\Section::make('Location & Movement')->schema([
                    Forms\Components\TextInput::make('gps_lat')->label('GPS Latitude')->numeric(),
                    Forms\Components\TextInput::make('gps_lng')->label('GPS Longitude')->numeric(),
                    Forms\Components\TextInput::make('speed_kph')->numeric()->suffix('km/h'),
                    Forms\Components\TextInput::make('idle_minutes')->numeric()->suffix('min'),
                    Forms\Components\Select::make('geofence_status')->options(['Inside' => 'Inside', 'Outside' => 'Outside', 'N/A' => 'N/A']),
                ])->columns(3),

                Forms\Components\Section::make('Engine & Faults')->schema([
                    Forms\Components\TextInput::make('engine_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('fuel_level')->numeric()->suffix('%'),
                    Forms\Components\TagsInput::make('fault_codes')->placeholder('Add fault code'),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_code')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('source')->badge(),
                Tables\Columns\TextColumn::make('recorded_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('speed_kph')->suffix(' km/h')->placeholder('—'),
                Tables\Columns\TextColumn::make('idle_minutes')->suffix(' min')->placeholder('—'),
                Tables\Columns\TextColumn::make('geofence_status')->badge()->color(fn (?string $state): string => match ($state) {
                    'Outside' => 'danger', 'Inside' => 'success', default => 'gray',
                })->placeholder('—'),
                Tables\Columns\TextColumn::make('fault_codes')->badge()->separator(',')->placeholder('None'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('equipment_id')->label('Equipment')->relationship('equipment', 'name'),
                Tables\Filters\SelectFilter::make('source')->options(self::getSourceOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('recorded_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTelematicsReadings::route('/'),
            'create' => Pages\CreateTelematicsReading::route('/create'),
            'edit' => Pages\EditTelematicsReading::route('/{record}/edit'),
        ];
    }
}
