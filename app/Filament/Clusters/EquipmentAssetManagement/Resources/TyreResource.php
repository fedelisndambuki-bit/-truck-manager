<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\TyreResource\Pages;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\TyreResource\RelationManagers;
use App\Models\Equipment;
use App\Models\Tyre;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TyreResource extends Resource
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

    protected static ?string $model = Tyre::class;

    protected static ?string $slug = 'tyre-management';

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    protected static ?string $navigationLabel = 'Tyre Management';

    protected static ?int $navigationSort = 16;

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Active' => 'success', 'Spare' => 'info', 'Under Repair' => 'warning', 'Discarded' => 'danger', 'New' => 'gray', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Tyre')->schema([
                    Forms\Components\TextInput::make('serial_number')->label('Tyre Number')->required(),
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('brand')->required(),
                    Forms\Components\TextInput::make('model'),
                    Forms\Components\TextInput::make('size')->required(),
                    Forms\Components\Select::make('type')->options(['New' => 'New', 'Retread' => 'Retread']),
                    Forms\Components\TextInput::make('position'),
                    Forms\Components\TextInput::make('tyre_category')->label('Category')->placeholder('Steer, Drive, Trailer'),
                    Forms\Components\Select::make('status')->options([
                        'New' => 'New', 'Active' => 'Active', 'Spare' => 'Spare', 'Under Repair' => 'Under Repair', 'Discarded' => 'Discarded',
                    ])->default('New')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Condition & Lifecycle')->schema([
                    Forms\Components\TextInput::make('tread_depth')->numeric()->suffix('mm'),
                    Forms\Components\TextInput::make('pressure')->numeric()->suffix('psi'),
                    Forms\Components\TextInput::make('retread_count')->numeric()->default(0),
                    Forms\Components\TextInput::make('expected_life_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('cost_per_hour')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('warehouse_location'),
                ])->columns(3),

                Forms\Components\Section::make('Installation')->schema([
                    Forms\Components\DatePicker::make('installation_date'),
                    Forms\Components\TextInput::make('installation_mileage')->numeric(),
                    Forms\Components\DatePicker::make('removal_date'),
                    Forms\Components\TextInput::make('removal_mileage')->numeric(),
                    Forms\Components\TextInput::make('removal_reason'),
                ])->columns(3),

                Forms\Components\Section::make('Purchase')->schema([
                    Forms\Components\DatePicker::make('purchase_date'),
                    Forms\Components\TextInput::make('supplier'),
                    Forms\Components\TextInput::make('purchase_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\Textarea::make('warranty_details')->columnSpanFull(),
                ])->columns(3),

                Forms\Components\Textarea::make('notes')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('serial_number')->label('Tyre No.')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->label('Equipment')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('position')->placeholder('—'),
                Tables\Columns\TextColumn::make('pressure')->suffix(' psi')->placeholder('—'),
                Tables\Columns\TextColumn::make('tread_depth')->suffix(' mm')->placeholder('—'),
                Tables\Columns\TextColumn::make('retread_count')->label('Retreads'),
                Tables\Columns\TextColumn::make('cost_per_hour')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    'New' => 'New', 'Active' => 'Active', 'Spare' => 'Spare', 'Under Repair' => 'Under Repair', 'Discarded' => 'Discarded',
                ]),
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
            ->striped();
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InspectionsRelationManager::class,
            RelationManagers\RepairsRelationManager::class,
            RelationManagers\RotationLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTyres::route('/'),
            'create' => Pages\CreateTyre::route('/create'),
            'edit' => Pages\EditTyre::route('/{record}/edit'),
        ];
    }
}
