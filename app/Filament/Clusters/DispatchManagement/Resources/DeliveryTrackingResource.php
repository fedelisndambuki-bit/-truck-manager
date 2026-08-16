<?php

namespace App\Filament\Clusters\DispatchManagement\Resources;

use App\Filament\Clusters\DispatchManagement;
use App\Filament\Clusters\DispatchManagement\Resources\DeliveryTrackingResource\Pages;
use App\Models\DispatchOrder;
use App\Traits\HasModuleAccess;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * "Delivery Tracking" — trucks currently on the road (Exited/En
 * Route/Delayed). GPS fields are schema-ready and manually updated via the
 * Update Location action — no live telematics/GPS device integration, this
 * mirrors Smart Weighbridge's identical scoping for gate GPS.
 */
class DeliveryTrackingResource extends Resource
{
    use HasModuleAccess;

    public static function getRequiredIndustries(): array
    {
        return ['quarry'];
    }

    public static function getModuleSlug(): ?string
    {
        return 'dispatch.view';
    }

    protected static ?string $cluster = DispatchManagement::class;

    protected static ?string $model = DispatchOrder::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';

    protected static ?string $navigationLabel = 'Delivery Tracking';

    protected static ?int $navigationSort = 7;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['customer'])->whereIn('status', ['Exited', 'En Route', 'Delayed']);
    }

    public static function getStatusColor(string $state): string
    {
        return match ($state) {
            'Delayed' => 'danger',
            'En Route' => 'info',
            default => 'gray',
        };
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('exit_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('dispatch_number')->label('Dispatch #')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('customer.company_name')->label('Customer')->placeholder('—'),
                Tables\Columns\TextColumn::make('vehicle_registration')->label('Vehicle')->placeholder('—'),
                Tables\Columns\TextColumn::make('delivery_address')->label('Destination')->placeholder('—')->limit(30),
                Tables\Columns\TextColumn::make('exit_at')->label('Departed')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('eta')->label('ETA')->dateTime('d M Y H:i')->placeholder('—'),
                Tables\Columns\TextColumn::make('distance_remaining_km')->label('Distance Left')->suffix(' km')->placeholder('—'),
                Tables\Columns\TextColumn::make('status')->badge()->color(fn (string $state): string => self::getStatusColor($state)),
            ])
            ->actions([
                Tables\Actions\Action::make('updateLocation')
                    ->label('Update Location')
                    ->icon('heroicon-o-map')
                    ->color('info')
                    ->visible(fn (DispatchOrder $record) => in_array($record->status, ['Exited', 'En Route']))
                    ->form([
                        Forms\Components\TextInput::make('current_gps_lat')->label('Latitude')->numeric(),
                        Forms\Components\TextInput::make('current_gps_lng')->label('Longitude')->numeric(),
                        Forms\Components\TextInput::make('distance_remaining_km')->label('Distance Remaining')->numeric()->suffix('km'),
                        Forms\Components\DateTimePicker::make('eta')->label('Updated ETA'),
                    ])
                    ->action(function (DispatchOrder $record, array $data) {
                        $record->updateLiveLocation($data);
                        Notification::make()->title('Location updated')->success()->send();
                    }),
                Tables\Actions\Action::make('recordArrival')
                    ->label('Record Arrival')
                    ->icon('heroicon-o-flag')
                    ->color('success')
                    ->visible(fn (DispatchOrder $record) => in_array($record->status, ['Exited', 'En Route']))
                    ->form([
                        Forms\Components\Textarea::make('delivery_notes')->label('Arrival Notes'),
                    ])
                    ->action(function (DispatchOrder $record, array $data) {
                        $record->recordArrival($data);
                        Notification::make()->title('Arrival recorded — proceed to Delivery Confirmation for proof of delivery')->success()->send();
                    }),
                Tables\Actions\Action::make('markDelayed')
                    ->label('Mark Delayed')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (DispatchOrder $record) => in_array($record->status, ['Exited', 'En Route']))
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('reason')->label('Delay Reason')->required(),
                    ])
                    ->action(function (DispatchOrder $record, array $data) {
                        $record->markDelayed($data['reason']);
                        Notification::make()->title('Marked as delayed')->warning()->send();
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->striped();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDeliveryTrackings::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
