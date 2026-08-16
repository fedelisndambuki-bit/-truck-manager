<?php

namespace App\Filament\Clusters\EquipmentAssetManagement\Resources;

use App\Filament\Clusters\EquipmentAssetManagement;
use App\Filament\Clusters\EquipmentAssetManagement\Resources\MaintenanceWorkOrderResource\Pages;
use App\Models\Equipment;
use App\Models\MaintenanceWorkOrder;
use App\Models\Part;
use App\Services\MaintenanceWorkOrderService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class MaintenanceWorkOrderResource extends Resource
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

    protected static ?string $slug = 'work-orders';

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Work Orders';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['equipment', 'technician']);
    }

    public static function getTypeOptions(): array
    {
        return array_combine(MaintenanceWorkOrder::TYPES, MaintenanceWorkOrder::TYPES);
    }

    public static function getPriorityOptions(): array
    {
        return array_combine(MaintenanceWorkOrder::PRIORITIES, MaintenanceWorkOrder::PRIORITIES);
    }

    public static function getStatusOptions(): array
    {
        return array_combine(MaintenanceWorkOrder::STATUSES, MaintenanceWorkOrder::STATUSES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Created' => 'gray', 'Assigned' => 'info', 'In Progress' => 'warning',
            'Waiting Parts' => 'danger', 'Completed' => 'success', 'Closed' => 'success',
            default => 'gray',
        };
    }

    public static function getPriorityColor(string $state): string
    {
        return match ($state) {
            'Low' => 'gray', 'Medium' => 'info', 'High' => 'warning', 'Critical' => 'danger', default => 'gray',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Work Order')->schema([
                    Forms\Components\TextInput::make('work_order_number')->disabled()->dehydrated(false)->placeholder('Auto-generated'),
                    Forms\Components\Select::make('equipment_id')
                        ->relationship('equipment', 'name')
                        ->getOptionLabelFromRecordUsing(fn (Equipment $record) => "{$record->name} ({$record->equipment_code})")
                        ->searchable()->preload()->required(),
                    Forms\Components\Select::make('work_order_type')->options(self::getTypeOptions())->default('Corrective')->required(),
                    Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Medium')->required(),
                    Forms\Components\Select::make('technician_id')->label('Technician')->relationship('technician', 'full_name')->searchable()->preload(),
                    Forms\Components\Select::make('workshop_id')->relationship('workshop', 'name')->searchable()->preload(),
                    Forms\Components\Select::make('workshop_bay_id')->label('Bay')->relationship('workshopBay', 'bay_number')->searchable()->preload(),
                    Forms\Components\Select::make('status')->options(self::getStatusOptions())->default('Created')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Effort & Cost')->schema([
                    Forms\Components\TextInput::make('estimated_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('actual_hours')->numeric()->suffix('hrs'),
                    Forms\Components\TextInput::make('labor_cost')->numeric()->prefix(session('currency', 'KES')),
                    Forms\Components\TextInput::make('outside_services_cost')->numeric()->prefix(session('currency', 'KES')),
                ])->columns(4),

                Forms\Components\Section::make('Parts & Description')->schema([
                    Forms\Components\Repeater::make('required_parts')
                        ->schema([
                            Forms\Components\Select::make('part_id')->label('Part')->options(fn () => Part::query()->pluck('name', 'id'))->searchable()->required(),
                            Forms\Components\TextInput::make('quantity')->numeric()->default(1)->required(),
                        ])->columns(2)->addActionLabel('Add Part')->columnSpanFull(),
                    Forms\Components\Textarea::make('description')->columnSpanFull(),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('work_order_number')->label('WO#')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('equipment.name')->searchable(),
                Tables\Columns\TextColumn::make('work_order_type')->label('Type')->badge(),
                Tables\Columns\TextColumn::make('priority')->badge()->color(fn (string $state): string => self::getPriorityColor($state)),
                Tables\Columns\TextColumn::make('technician.full_name')->label('Technician')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
                Tables\Columns\TextColumn::make('total_cost')->money(session('currency', 'KES'))->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('work_order_type')->label('Type')->options(self::getTypeOptions()),
                Tables\Filters\SelectFilter::make('status')->options(self::getStatusOptions()),
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('start')
                    ->label('Start')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn (MaintenanceWorkOrder $record) => in_array($record->status, ['Created', 'Assigned']))
                    ->action(fn (MaintenanceWorkOrder $record) => app(MaintenanceWorkOrderService::class)->start($record)),
                Tables\Actions\Action::make('waitingParts')
                    ->label('Waiting Parts')
                    ->icon('heroicon-o-cube')
                    ->color('danger')
                    ->visible(fn (MaintenanceWorkOrder $record) => $record->status === 'In Progress')
                    ->action(fn (MaintenanceWorkOrder $record) => app(MaintenanceWorkOrderService::class)->markWaitingParts($record)),
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (MaintenanceWorkOrder $record) => in_array($record->status, ['In Progress', 'Waiting Parts']))
                    ->form([
                        Forms\Components\TextInput::make('actual_hours')->numeric()->suffix('hrs'),
                    ])
                    ->action(fn (MaintenanceWorkOrder $record, array $data) => app(MaintenanceWorkOrderService::class)->complete($record, $data['actual_hours'] ?? null)),
                Tables\Actions\Action::make('close')
                    ->label('Close')
                    ->icon('heroicon-o-lock-closed')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->modalDescription('This posts the service history, deducts spare parts and records the maintenance cost. This cannot be undone.')
                    ->visible(fn (MaintenanceWorkOrder $record) => $record->status === 'Completed')
                    ->action(function (MaintenanceWorkOrder $record) {
                        try {
                            app(MaintenanceWorkOrderService::class)->close($record, Auth::id());
                        } catch (\RuntimeException $e) {
                            Notification::make()->title('Could not close work order')->body($e->getMessage())->danger()->send();
                            return;
                        }

                        Notification::make()->title("Work order {$record->work_order_number} closed")->success()->send();
                    }),
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
            'index' => Pages\ListMaintenanceWorkOrders::route('/'),
            'create' => Pages\CreateMaintenanceWorkOrder::route('/create'),
            'edit' => Pages\EditMaintenanceWorkOrder::route('/{record}/edit'),
        ];
    }
}
