<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\CorrectiveMaintenanceResource\Pages;
use App\Models\Equipment;
use App\Models\MaintenanceWorkOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * "Planned repair" work — the same maintenance_work_orders table as Work
 * Orders, scoped to work_order_type = Corrective, so Corrective Maintenance
 * gets its own sidebar entry without a redundant table (Breakdown-triggered
 * jobs stay under Breakdowns/Work Orders with type = Breakdown).
 */
class CorrectiveMaintenanceResource extends Resource
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

    protected static ?string $model = MaintenanceWorkOrder::class;

    protected static ?string $slug = 'corrective-maintenance';

    protected static ?string $navigationIcon = 'heroicon-o-wrench';

    protected static ?string $navigationLabel = 'Corrective Maintenance';

    protected static ?int $navigationSort = 10;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('work_order_type', 'Corrective')->with(['equipment', 'technician']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Corrective Work Order')->schema([
                    Forms\Components\Hidden::make('work_order_type')->default('Corrective'),
                    Forms\Components\TextInput::make('work_order_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('priority')->options(MaintenanceWorkOrderResource::getPriorityOptions())->default('Medium')->required(),
                    Forms\Components\Select::make('technician_id')->label('Technician')->relationship('technician', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(MaintenanceWorkOrderResource::getStatusOptions())->default('Created')->required(),
                    Forms\Components\TextInput::make('estimated_hours')->numeric()->suffix('hrs'),
                ])->columns(3),

                Forms\Components\Textarea::make('description')->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('work_order_number')->label('WO#')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state): string => MaintenanceWorkOrderResource::getPriorityColor($state)),
                Tables\Columns\TextColumn::make('technician.full_name')->label('Technician')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => MaintenanceWorkOrderResource::getStatusColor($state)),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(MaintenanceWorkOrderResource::getStatusOptions()),
                Tables\Filters\SelectFilter::make('priority')->options(MaintenanceWorkOrderResource::getPriorityOptions()),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCorrectiveMaintenance::route('/'),
            'create' => Pages\CreateCorrectiveMaintenance::route('/create'),
            'edit' => Pages\EditCorrectiveMaintenance::route('/{record}/edit'),
        ];
    }
}
