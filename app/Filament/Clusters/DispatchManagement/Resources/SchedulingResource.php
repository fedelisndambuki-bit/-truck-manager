<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\SchedulingResource\Pages;
use App\Models\DispatchOrder;
use App\Models\Employee;
use App\Models\Truck;
use App\Services\Quarry\DispatchSchedulingService;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Scheduling" — Draft/Planned dispatch orders awaiting a truck and driver.
 * Assign Truck / Assign Driver actions use DispatchSchedulingService's real
 * fitness checks (status/insurance/road-license/license-expiry/daily-trip
 * cap) to pre-fill a recommendation and warn on unfit choices — no
 * fabricated AI, just real comparisons against real Truck/Employee columns.
 */
class SchedulingResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = DispatchManagement::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'dispatch.view';
    }

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Scheduling';

    protected static ?int $navigationSort = 3;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereIn('status', ['Draft', 'Planned']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dispatch')->schema([
                    Forms\Components\Select::make('customer_order_id')->label('Customer Order')
                        ->relationship('customerOrder', 'order_number')->searchable()->preload(),
                    Forms\Components\Select::make('customer_id')->label('Customer')
                        ->relationship('customer', 'company_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('target_net_weight')->label('Target Weight')->numeric()->suffix('t'),
                    Forms\Components\Select::make('priority')->options(array_combine(DispatchOrder::PRIORITIES, DispatchOrder::PRIORITIES))->default('Normal'),
                    Forms\Components\Select::make('status')->options(['Draft' => 'Draft', 'Planned' => 'Planned'])->required(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('priority', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customerOrder.order_number')->label('Order #')->placeholder('—'),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->placeholder('—'),
                Tables\Columns\TextColumn::make('material.name')->label('Product')->placeholder('—'),
                Tables\Columns\TextColumn::make('target_net_weight')->label('Target')->suffix(' t')->placeholder('—'),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Truck')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('driver_name')->label('Driver')->placeholder('Unassigned'),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('status')->badge(),
            ])
            ->actions([
                Tables\Actions\Action::make('assignTruck')
                    ->label('Assign Truck')
                    ->icon('heroicon-o-truck')
                    ->color('info')
                    ->form(function (DispatchOrder $record) {
                        $service = app(DispatchSchedulingService::class);
                        $recommended = $service->recommendTruck($record->company_id, (float) ($record->target_net_weight ?? 0));

                        return [
                            Forms\Components\Select::make('truck_id')
                                ->label('Truck')
                                ->options(Truck::where('company_id', $record->company_id)->get()->mapWithKeys(
                                    fn (Truck $t) => [$t->id => $t->plate_number . ($service->truckPassesFitness($t, $record->company_id) ? '' : ' — NOT FIT')]
                                ))
                                ->default($recommended?->id)
                                ->helperText($recommended ? "Recommended: {$recommended->plate_number} (least-loaded fit truck)" : 'No fully-fit truck available — choose carefully')
                                ->required()
                                ->searchable(),
                        ];
                    })
                    ->action(function (DispatchOrder $record, array $data) {
                        $truck = Truck::find($data['truck_id']);
                        $service = app(DispatchSchedulingService::class);
                        $checks = $service->truckFitnessCheck($truck, $record->company_id);

                        $record->update([
                            'vehicle_registration' => $truck->plate_number,
                            'status' => 'Planned',
                        ]);

                        if (in_array(false, $checks, true)) {
                            $failed = implode(', ', array_keys(array_filter($checks, fn ($v) => $v === false)));
                            Notification::make()->title("Truck assigned with warnings: failed {$failed}")->warning()->send();
                        } else {
                            Notification::make()->title("Truck {$truck->plate_number} assigned")->success()->send();
                        }
                    }),
                Tables\Actions\Action::make('assignDriver')
                    ->label('Assign Driver')
                    ->icon('heroicon-o-user')
                    ->color('info')
                    ->form(function (DispatchOrder $record) {
                        $service = app(DispatchSchedulingService::class);
                        $recommended = $service->recommendDriver($record->company_id);

                        return [
                            Forms\Components\Select::make('employee_id')
                                ->label('Driver')
                                ->options(Employee::where('company_id', $record->company_id)->where('status', 'Active')->get()->mapWithKeys(
                                    fn (Employee $e) => [$e->id => $e->full_name . ($service->driverPassesFitness($e, $record->company_id) ? '' : ' — NOT FIT')]
                                ))
                                ->default($recommended?->id)
                                ->helperText($recommended ? "Recommended: {$recommended->full_name} (least-loaded fit driver)" : 'No fully-fit driver available — choose carefully')
                                ->required()
                                ->searchable(),
                        ];
                    })
                    ->action(function (DispatchOrder $record, array $data) {
                        $employee = Employee::find($data['employee_id']);
                        $service = app(DispatchSchedulingService::class);
                        $checks = $service->driverFitnessCheck($employee, $record->company_id);

                        $record->update([
                            'driver_name' => $employee->full_name,
                            'driver_license_number' => $employee->license_number,
                            'driver_phone' => $employee->phone_number,
                        ]);

                        if (in_array(false, $checks, true)) {
                            $failed = implode(', ', array_keys(array_filter($checks, fn ($v) => $v === false)));
                            Notification::make()->title("Driver assigned with warnings: failed {$failed}")->warning()->send();
                        } else {
                            Notification::make()->title("Driver {$employee->full_name} assigned")->success()->send();
                        }
                    }),
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
            'index' => Pages\ListSchedules::route('/'),
            'create' => Pages\CreateSchedule::route('/create'),
            'edit' => Pages\EditSchedule::route('/{record}/edit'),
        ];
    }
}
