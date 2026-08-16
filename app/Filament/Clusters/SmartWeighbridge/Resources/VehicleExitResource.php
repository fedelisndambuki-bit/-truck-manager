<?php

namespace App\Filament\Clusters\SmartWeighbridge\Resources;

use App\Filament\Clusters\SmartWeighbridge;
use App\Filament\Clusters\SmartWeighbridge\Resources\VehicleExitResource\Pages;
use App\Models\DispatchOrder;
use App\Models\ProofOfDelivery;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * 5th Resource over DispatchOrder — scoped to approved trucks awaiting exit.
 * Captures exit fields and creates the Proof of Delivery record.
 */
class VehicleExitResource extends Resource
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

    protected static ?string $navigationIcon = 'heroicon-o-arrow-right-on-rectangle';

    protected static ?string $navigationLabel = 'Vehicle Exit';

    protected static ?int $navigationSort = 12;

    protected static ?string $slug = 'vehicle-exit';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->whereIn('status', ['Approved', 'Exited']))
            ->defaultSort('exit_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable(),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->searchable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer'),
                Tables\Columns\TextColumn::make('net_weight')->label('Net Weight')->suffix(' t')->placeholder('—'),
                Tables\Columns\IconColumn::make('can_exit')
                    ->label('Ready to Exit')
                    ->state(fn (DispatchOrder $record) => $record->canExit())
                    ->boolean(),
                Tables\Columns\TextColumn::make('exit_at')->label('Exit Time')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => VehicleCheckInResource::getStatusColor($state)),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->infolist(fn (Infolist $infolist) => VehicleCheckInResource::infolist($infolist)),
                Tables\Actions\Action::make('recordExit')
                    ->label('Record Exit')
                    ->icon('heroicon-o-arrow-right-on-rectangle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (DispatchOrder $record) => $record->status === 'Approved' && $record->canExit())
                    ->form([
                        Forms\Components\TextInput::make('exit_gate')->label('Exit Gate'),
                        Forms\Components\Select::make('security_clearance_by_id')->label('Security Officer')
                            ->relationship('securityClearanceBy', 'full_name')->searchable()->preload(),
                        Forms\Components\TextInput::make('receiver_name')->label('Receiver Name'),
                        Forms\Components\TextInput::make('delivered_quantity')->label('Delivered Quantity')->numeric()->suffix('t'),
                    ])
                    ->action(function (DispatchOrder $record, array $data) {
                        $record->recordExit($data);

                        ProofOfDelivery::create([
                            'company_id' => $record->company_id,
                            'dispatch_order_id' => $record->id,
                            'delivered_quantity' => $data['delivered_quantity'] ?? $record->net_weight,
                            'delivery_time' => now(),
                            'receiver_name' => $data['receiver_name'] ?? null,
                            'status' => 'Delivered',
                        ]);

                        Notification::make()->title('Vehicle exited — Proof of Delivery recorded')->success()->send();
                    }),
            ])
            ->striped();
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['customer']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleExits::route('/'),
        ];
    }
}
