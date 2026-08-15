<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentHealthReadingResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentHealthReading;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentHealthReadingResource extends Resource
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

    protected static ?string $model = EquipmentHealthReading::class;

    protected static ?string $slug = 'equipment-health';

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Equipment Health';

    protected static ?int $navigationSort = 6;

    public static function getHealthColor(?int $score): string
    {
        return match (true) {
            $score === null => 'gray',
            $score >= 80 => 'success',
            $score >= 50 => 'warning',
            default => 'danger',
        };
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
                    Forms\Components\DateTimePicker::make('recorded_at')->default(now())->required(),
                    Forms\Components\TextInput::make('health_score')->numeric()->minValue(0)->maxValue(100)->suffix('/100'),
                ])->columns(3),

                Forms\Components\Section::make('Engine & Hydraulics')->schema([
                    Forms\Components\TextInput::make('engine_temp')->numeric()->suffix('°C'),
                    Forms\Components\TextInput::make('oil_pressure')->numeric()->suffix('psi'),
                    Forms\Components\TextInput::make('hydraulic_pressure')->numeric()->suffix('psi'),
                    Forms\Components\TextInput::make('coolant_temp')->numeric()->suffix('°C'),
                    Forms\Components\TextInput::make('battery_voltage')->numeric()->suffix('V'),
                    Forms\Components\TextInput::make('rpm')->numeric(),
                    Forms\Components\TextInput::make('engine_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('transmission_status'),
                ])->columns(4),

                Forms\Components\Section::make('Fluids & Faults')->schema([
                    Forms\Components\TextInput::make('fuel_level')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('def_level')->label('DEF Level')->numeric()->suffix('%'),
                    Forms\Components\TextInput::make('hydraulic_oil_level')->numeric()->suffix('%'),
                    Forms\Components\TagsInput::make('error_codes')->placeholder('Add fault code'),
                ])->columns(4),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_code')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('recorded_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('engine_temp')->suffix(' °C')->placeholder('—'),
                Tables\Columns\TextColumn::make('hydraulic_pressure')->suffix(' psi')->placeholder('—'),
                Tables\Columns\TextColumn::make('fuel_level')->suffix('%')->placeholder('—'),
                Tables\Columns\TextColumn::make('error_codes')->badge()->separator(',')->placeholder('None'),
                Tables\Columns\TextColumn::make('health_score')->label('Health Score')->badge()->color(fn (?int $state): string => self::getHealthColor($state))->placeholder('—'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('equipment_id')->label('Equipment')->relationship('equipment', 'name'),
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
            'index' => Pages\ListEquipmentHealthReadings::route('/'),
            'create' => Pages\CreateEquipmentHealthReading::route('/create'),
            'edit' => Pages\EditEquipmentHealthReading::route('/{record}/edit'),
        ];
    }
}
