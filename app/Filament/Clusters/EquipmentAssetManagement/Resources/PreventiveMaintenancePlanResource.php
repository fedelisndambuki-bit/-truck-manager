<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\PreventiveMaintenancePlanResource\Pages;
use App\Models\Equipment;
use App\Models\Part;
use App\Models\PreventiveMaintenancePlan;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PreventiveMaintenancePlanResource extends Resource
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

    protected static ?string $model = PreventiveMaintenancePlan::class;

    protected static ?string $slug = 'preventive-maintenance';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Preventive Maintenance';

    protected static ?int $navigationSort = 9;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['equipment']);
    }

    public static function getTriggerTypeOptions(): array
    {
        return array_combine(PreventiveMaintenancePlan::TRIGGER_TYPES, PreventiveMaintenancePlan::TRIGGER_TYPES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(PreventiveMaintenancePlan::STATUSES, PreventiveMaintenancePlan::STATUSES);
    }

    public static function intervalUnitFor(?string $triggerType): string
    {
        return match ($triggerType) {
            'Calendar' => 'days',
            'EngineHours' => 'hrs',
            'Km' => 'km',
            'FuelUsage' => 'L',
            'ProductionCycles' => 'cycles',
            default => '',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Plan')->schema([
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('name')->label('Plan Name')->required(),
                    Forms\Components\Select::make('trigger_type')->options(self::getTriggerTypeOptions())->default('Calendar')->required()->live(),
                    Forms\Components\TextInput::make('interval_value')->label('Interval')->numeric()->required()
                        ->suffix(fn (Forms\Get $get) => self::intervalUnitFor($get('trigger_type'))),
                    Forms\Components\TextInput::make('estimated_duration_hours')->numeric()->suffix('hrs'),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Active')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Requirements')->schema([
                    Forms\Components\Repeater::make('required_parts')
                        ->schema([
                            Forms\Components\Select::make('part_id')->label('Part')->options(fn () => Part::query()->pluck('name', 'id'))->searchable()->required(),
                            Forms\Components\TextInput::make('quantity')->numeric()->default(1)->required(),
                        ])->columns(2)->addActionLabel('Add Part')->columnSpanFull(),
                    Forms\Components\TagsInput::make('required_tools')->placeholder('Add tool')->columnSpanFull(),
                    Forms\Components\TagsInput::make('required_skills')->placeholder('Add skill')->columnSpanFull(),
                    Forms\Components\KeyValue::make('checklist')->keyLabel('Item')->valueLabel('Detail')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->searchable(),
                Tables\Columns\TextColumn::make('trigger_type')->badge(),
                Tables\Columns\TextColumn::make('interval_value')->label('Interval'),
                Tables\Columns\TextColumn::make('next_due_value')->label('Next Due')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => $state === 'Active' ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('trigger_type')->options(self::getTriggerTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
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
            'index' => Pages\ListPreventiveMaintenancePlans::route('/'),
            'create' => Pages\CreatePreventiveMaintenancePlan::route('/create'),
            'edit' => Pages\EditPreventiveMaintenancePlan::route('/{record}/edit'),
        ];
    }
}
