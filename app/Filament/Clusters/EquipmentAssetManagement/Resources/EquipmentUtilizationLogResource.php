<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentUtilizationLogResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentUtilizationLog;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentUtilizationLogResource extends Resource
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

    protected static ?string $model = EquipmentUtilizationLog::class;

    protected static ?string $slug = 'equipment-utilization';

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationLabel = 'Equipment Utilization';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Log Entry')->schema([
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\DatePicker::make('log_date')->default(now())->required(),
                ])->columns(2),

                Forms\Components\Section::make('Hours')->schema([
                    Forms\Components\TextInput::make('engine_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('operating_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('idle_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('travel_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('standby_hours')->numeric()->suffix('hrs'),
                ])->columns(3),

                Forms\Components\Section::make('Production')->schema([
                    Forms\Components\TextInput::make('trips')->numeric(),
                    Forms\Components\TextInput::make('cycles')->numeric(),
                    Forms\Components\TextInput::make('tonnes_moved')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('material_loaded')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('fuel_used_litres')->numeric()->suffix('L'),
                    Forms\Components\TextInput::make('distance_km')->numeric()->suffix('km'),
                ])->columns(3),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_code')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('log_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('operating_hours')->suffix(' hrs')->placeholder('—'),
                Tables\Columns\TextColumn::make('idle_hours')->suffix(' hrs')->placeholder('—'),
                Tables\Columns\TextColumn::make('tonnes_moved')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('utilization_percent')->label('Utilization %')->suffix('%')->state(fn (EquipmentUtilizationLog $record) => $record->utilization_percent),
                Tables\Columns\TextColumn::make('availability_percent')->label('Availability %')->suffix('%')->state(fn (EquipmentUtilizationLog $record) => $record->availability_percent),
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
            ->defaultSort('log_date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentUtilizationLogs::route('/'),
            'create' => Pages\CreateEquipmentUtilizationLog::route('/create'),
            'edit' => Pages\EditEquipmentUtilizationLog::route('/{record}/edit'),
        ];
    }
}
