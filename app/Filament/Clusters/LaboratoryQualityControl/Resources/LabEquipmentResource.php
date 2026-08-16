<?php

namespace App\Filament\Clusters\LaboratoryQualityControl\Resources;

use App\Filament\Clusters\LaboratoryQualityControl;
use App\Filament\Clusters\LaboratoryQualityControl\Resources\LabEquipmentResource\Pages;
use App\Models\LabEquipment;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Laboratory Equipment" — dedicated model (not the generic Equipment used
 * by fleet/construction), matching the precedent already set by Weighbridge/
 * LoadingBay not reusing generic Equipment for their own hardware.
 */
class LabEquipmentResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = LaboratoryQualityControl::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'lab.view';
    }

    protected static ?string $model = LabEquipment::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Laboratory Equipment';

    protected static ?int $navigationSort = 11;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Operational' => 'success',
            'Due Calibration' => 'warning',
            'Out of Service', 'Under Maintenance' => 'danger',
            default => 'gray',
        };
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['responsibleTechnician']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Equipment')->schema([
                    Forms\Components\TextInput::make('equipment_number')->label('Equipment #')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('manufacturer'),
                    Forms\Components\TextInput::make('model'),
                    Forms\Components\TextInput::make('serial_number'),
                    Forms\Components\Select::make('responsible_technician_id')->label('Responsible Technician')
                        ->relationship('responsibleTechnician', 'full_name')->searchable()->preload(),
                    Forms\Components\DatePicker::make('calibration_due_date'),
                    Forms\Components\DatePicker::make('maintenance_due_date'),
                    Forms\Components\Select::make('status')->options(array_combine(LabEquipment::STATUSES, LabEquipment::STATUSES))->default('Operational')->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment_number')->label('Equipment #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('manufacturer')->placeholder('—'),
                Tables\Columns\TextColumn::make('serial_number')->placeholder('—'),
                Tables\Columns\TextColumn::make('responsibleTechnician.full_name')->label('Technician')->placeholder('—'),
                Tables\Columns\TextColumn::make('calibration_due_date')->date('d M Y')->sortable()->placeholder('—'),
                Tables\Columns\IconColumn::make('calibration_overdue')->label('Overdue')->boolean()
                    ->state(fn (LabEquipment $record) => $record->isCalibrationOverdue()),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(array_combine(LabEquipment::STATUSES, LabEquipment::STATUSES)),
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

    public static function getGloballySearchableAttributes(): array
    {
        return ['equipment_number', 'name', 'serial_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLabEquipment::route('/'),
            'create' => Pages\CreateLabEquipment::route('/create'),
            'edit' => Pages\EditLabEquipment::route('/{record}/edit'),
        ];
    }
}
