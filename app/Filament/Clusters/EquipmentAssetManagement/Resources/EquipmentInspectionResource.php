<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\EquipmentInspectionResource\Pages;
use App\Models\Equipment;
use App\Models\EquipmentInspection;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EquipmentInspectionResource extends Resource
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

    protected static ?string $model = EquipmentInspection::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    protected static ?string $navigationLabel = 'Inspections';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['equipment', 'inspector']);
    }

    public static function getTypeOptions(): array
    {
        return array_combine(EquipmentInspection::TYPES, EquipmentInspection::TYPES);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Inspection')->schema([
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('type')->options(self::getTypeOptions())->default('Daily')->required(),
                    Forms\Components\DatePicker::make('date')->default(now())->required(),
                    Forms\Components\Select::make('inspector_id')->label('Inspector')->relationship('inspector', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('operator_id')->label('Operator')->relationship('operator', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('gps_coordinates'),
                ])->columns(3),

                Forms\Components\Section::make('Checklist & Findings')->schema([
                    Forms\Components\KeyValue::make('checklist')->keyLabel('Item')->valueLabel('Result')->columnSpanFull(),
                    Forms\Components\Toggle::make('pass_fail')->label('Passed')->helperText('Toggle on if the inspection passed overall.'),
                    Forms\Components\Textarea::make('defects')->columnSpanFull(),
                    Forms\Components\Textarea::make('corrective_actions')->columnSpanFull(),
                    Forms\Components\FileUpload::make('photos')->image()->multiple()->disk('public')->directory('equipment-inspections')->columnSpanFull(),
                    Forms\Components\FileUpload::make('videos')->multiple()->disk('public')->directory('equipment-inspections-videos')->columnSpanFull(),
                    Forms\Components\Toggle::make('supervisor_approved')->label('Supervisor Approved'),
                    Forms\Components\Textarea::make('notes')->columnSpanFull(),
                ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('equipment.equipment_code')->label('Equipment')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('date')->date()->sortable(),
                Tables\Columns\TextColumn::make('inspector.full_name')->label('Inspector')->placeholder('—'),
                Tables\Columns\IconColumn::make('pass_fail')->label('Result')->boolean()->placeholder('—'),
                Tables\Columns\IconColumn::make('supervisor_approved')->label('Approved')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(self::getTypeOptions()),
                Tables\Filters\SelectFilter::make('equipment_id')->label('Equipment')->relationship('equipment', 'name'),
                Tables\Filters\TernaryFilter::make('pass_fail')->label('Result')->trueLabel('Passed')->falseLabel('Failed'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('date', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEquipmentInspections::route('/'),
            'create' => Pages\CreateEquipmentInspection::route('/create'),
            'edit' => Pages\EditEquipmentInspection::route('/{record}/edit'),
        ];
    }
}
