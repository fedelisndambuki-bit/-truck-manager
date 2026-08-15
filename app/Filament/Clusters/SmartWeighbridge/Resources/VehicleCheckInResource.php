<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleCheckInResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * The digital gate check-in capture — a Create/List view over DispatchOrder
 * scoped to the initial arrival stage. Later stages (tare, loading, gross
 * weighing, approval, exit) each get their own differently-scoped Resource
 * over this same table, matching the established "one Resource per
 * workflow stage" pattern from Crushing Plant / Stockpile Management.
 */
class VehicleCheckInResource extends Resource
{
    use HasModuleAccess;

    protected static ?string $cluster = SmartWeighbridge::class;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'weighbridge.view';
    }

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationLabel = 'Vehicle Check-In';

    protected static ?int $navigationSort = 2;

    public static function getPriorityOptions(): array
    {
        return array_combine(DispatchOrder::PRIORITIES, DispatchOrder::PRIORITIES);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Approved', 'Exited' => 'success',
            'Pending Approval' => 'warning',
            'Rejected', 'Cancelled' => 'danger',
            default => 'info',
        };
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Vehicle & Driver')->schema([
                    Forms\Components\TextInput::make('vehicle_registration')->label('Vehicle Registration')->required(),
                    Forms\Components\TextInput::make('trailer_number')->label('Trailer Number'),
                    Forms\Components\TextInput::make('fleet_number')->label('Fleet Number'),
                    Forms\Components\Select::make('transport_company_id')->label('Transport Company')
                        ->relationship('transportCompany', 'name')->searchable()->preload(),
                    Forms\Components\TextInput::make('driver_name')->label('Driver'),
                    Forms\Components\TextInput::make('driver_national_id')->label('National ID'),
                    Forms\Components\TextInput::make('driver_license_number')->label('Driving Licence'),
                    Forms\Components\DatePicker::make('driver_license_expiry')->label('Licence Expiry'),
                    Forms\Components\TextInput::make('driver_phone')->label('Phone')->tel(),
                    Forms\Components\FileUpload::make('driver_photo_path')->label('Driver Photo')->image()->directory('weighbridge-documents'),
                ])->columns(3),

                Forms\Components\Section::make('Order & Product')->schema([
                    Forms\Components\Select::make('customer_id')->label('Customer')
                        ->relationship('customer', 'company_name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('order_number')->label('Order Number'),
                    Forms\Components\Select::make('material_id')->label('Product')
                        ->relationship('material', 'name')->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('product_grade')->label('Product Grade'),
                    Forms\Components\Select::make('stockpile_id')->label('Assigned Stockpile')
                        ->relationship('stockpile', 'label')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->label ?? ('Stockpile #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\Select::make('reservation_id')->label('Stock Reservation')
                        ->relationship('reservation', 'reservation_number')
                        ->getOptionLabelFromRecordUsing(fn ($record) => $record->reservation_number ?? ('Reservation #' . $record->id))
                        ->searchable()->preload(),
                    Forms\Components\TextInput::make('expected_quantity')->label('Reserved Quantity')->numeric()->suffix('t'),
                    Forms\Components\TextInput::make('target_net_weight')->label('Expected Net Weight')->numeric()->suffix('t')->required(),
                    Forms\Components\Select::make('priority')->options(self::getPriorityOptions())->default('Normal')->required(),
                ])->columns(3),

                Forms\Components\Section::make('Gate & Security')->schema([
                    Forms\Components\Select::make('weighbridge_id')->label('Weighbridge')
                        ->relationship('weighbridge', 'name')->searchable()->preload()->required(),
                    Forms\Components\DateTimePicker::make('gate_in_at')->label('Arrival Time')->default(now())->required(),
                    Forms\Components\TextInput::make('gate')->label('Gate'),
                    Forms\Components\Select::make('security_officer_id')->label('Security Officer')
                        ->relationship('securityOfficer', 'full_name')->searchable()->preload(),
                    Forms\Components\TextInput::make('gate_gps_lat')->label('GPS Latitude')->numeric(),
                    Forms\Components\TextInput::make('gate_gps_lng')->label('GPS Longitude')->numeric(),
                    Forms\Components\CheckboxList::make('security_checklist')
                        ->label('Security Checklist')
                        ->options([
                            'id_verified' => 'Driver ID Verified',
                            'license_valid' => 'Driving Licence Valid',
                            'vehicle_registration_matches' => 'Vehicle Registration Matches',
                            'no_blacklist' => 'Not on Blacklist',
                        ])->columns(2),
                    Forms\Components\Toggle::make('vehicle_inspection_passed')->label('Vehicle Inspection Passed'),
                    Forms\Components\Textarea::make('vehicle_inspection_notes')->label('Inspection Notes / Remarks')->columnSpanFull(),
                ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->searchable(),
                Tables\Columns\TextColumn::make('driver_name')->label('Driver')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('material.name')->label('Product'),
                Tables\Columns\TextColumn::make('gate_in_at')->label('Arrival')->dateTime('d M Y H:i')->sortable(),
                Tables\Columns\TextColumn::make('priority')->badge(),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('priority')->options(self::getPriorityOptions()),
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
        return ['dispatch_number', 'order_number', 'vehicle_registration', 'driver_name', 'driver_phone', 'trailer_number'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleCheckIns::route('/'),
            'create' => Pages\CreateVehicleCheckIn::route('/create'),
            'view' => Pages\ViewVehicleCheckIn::route('/{record}'),
            'edit' => Pages\EditVehicleCheckIn::route('/{record}/edit'),
        ];
    }
}
